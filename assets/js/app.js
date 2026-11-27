/* =====================================================================
 * QNU CAMPUSFIX 2.0 – ENGINE BẢN ĐỒ KHUÔN VIÊN 3D CHUYÊN NGHIỆP
 * Three.js Architectural Visualization + Day/Sunset/Night Lighting
 * + Live Incident 3D Pulse + Camera Presets + Compass HUD
 * ===================================================================== */

const API_LIST = 'api/get_reports.php';

const $ = (s, r = document) => r.querySelector(s);
const mapWrap   = $('#map-wrap');
const container = $('#campus-3d');
const labelsBox = $('#map-labels');
const tooltip   = $('#tooltip');
const panel     = $('#panel');
const overlay   = $('#overlay');

let currentZone   = null;
let hoveredZoneId = null;
let hiddenTypes   = new Set();
let reportsByZone = {};
let localReports  = JSON.parse(localStorage.getItem('myReports') || '[]');
let mapController = null;

/* ---------- Tiện ích chung ---------- */
function bbox(z) {
  if (z.rect) {
    const [x, y, w, h] = z.rect;
    return { x, y, w, h, cx: x + w / 2, cy: y + h / 2 };
  }
  const pts = z.poly.split(' ').map(p => p.split(',').map(Number));
  const xs = pts.map(p => p[0]), ys = pts.map(p => p[1]);
  const x = Math.min(...xs), y = Math.min(...ys);
  const w = Math.max(...xs) - x, h = Math.max(...ys) - y;
  return {
    x, y, w, h,
    cx: z.label ? z.label[0] : x + w / 2,
    cy: z.label ? z.label[1] : y + h / 2
  };
}

function getBuildingHeight(z) {
  if (z.arch === 'parking') return 28;
  const floors = z.floors || 3;
  return Math.max(46, floors * 24);
}

function toast(msg, err = false) {
  const t = $('#toast');
  if (!t) return;
  t.textContent = msg;
  t.className = 'toast' + (err ? ' err' : '');
  t.hidden = false;
  clearTimeout(toast._t);
  toast._t = setTimeout(() => (t.hidden = true), 3500);
}

function escapeHtml(s) {
  return String(s).replace(/[&<>"']/g, c => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  }[c]));
}

/* =====================================================================
 * ENGINE 1: THREE.JS WEBGL 3D CAMPUS ARCHITECTURAL VISUALIZATION (v2.0)
 * ===================================================================== */
function initThreeMap() {
  const MAP_W = CAMPUS_SIZE.width;
  const MAP_D = CAMPUS_SIZE.depth;

  const scene = new THREE.Scene();

  /* ---------- Sky Dome Gradient (Sáng / Hoàng hôn / Đêm) ---------- */
  const skyThemes = {
    day: {
      top: 0x38bdf8,
      horizon: 0xe0f2fe,
      fog: 0xdbeafe,
      fogDensity: 0.000085,
      hemiSky: 0xf0f9ff,
      hemiGround: 0x86efac,
      hemiInt: 0.78,
      sunColor: 0xfffbeb,
      sunInt: 1.05,
      sunPos: [1050, 1750, 1150],
      fillColor: 0x93c5fd,
      fillInt: 0.35,
      winGlow: 0.0,
      lampGlow: 0.0
    },
    sunset: {
      top: 0x312e81,
      horizon: 0xfdba74,
      fog: 0xfed7aa,
      fogDensity: 0.00011,
      hemiSky: 0xfed7aa,
      hemiGround: 0x475569,
      hemiInt: 0.62,
      sunColor: 0xff8c42,
      sunInt: 1.15,
      sunPos: [-1600, 620, 950],
      fillColor: 0x818cf8,
      fillInt: 0.32,
      winGlow: 0.45,
      lampGlow: 0.65
    },
    night: {
      top: 0x020617,
      horizon: 0x1e293b,
      fog: 0x0f172a,
      fogDensity: 0.00013,
      hemiSky: 0x334155,
      hemiGround: 0x0f172a,
      hemiInt: 0.42,
      sunColor: 0x93c5fd,
      sunInt: 0.45,
      sunPos: [900, 1400, -1000],
      fillColor: 0x38bdf8,
      fillInt: 0.25,
      winGlow: 1.15,
      lampGlow: 1.35
    }
  };

  let currentTheme = 'day';
  scene.fog = new THREE.FogExp2(skyThemes.day.fog, skyThemes.day.fogDensity);

  const skyGeo = new THREE.SphereGeometry(9500, 32, 16);
  const skyUniforms = {
    topColor: { value: new THREE.Color(skyThemes.day.top) },
    bottomColor: { value: new THREE.Color(skyThemes.day.horizon) },
    offset: { value: 260 },
    exponent: { value: 0.65 }
  };
  const skyMat = new THREE.ShaderMaterial({
    uniforms: skyUniforms,
    vertexShader: `
      varying vec3 vWorldPosition;
      void main() {
        vec4 worldPosition = modelMatrix * vec4(position, 1.0);
        vWorldPosition = worldPosition.xyz;
        gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0);
      }
    `,
    fragmentShader: `
      uniform vec3 topColor;
      uniform vec3 bottomColor;
      uniform float offset;
      uniform float exponent;
      varying vec3 vWorldPosition;
      void main() {
        float h = normalize(vWorldPosition + vec3(0.0, offset, 0.0)).y;
        gl_FragColor = vec4(mix(bottomColor, topColor, max(pow(max(h, 0.0), exponent), 0.0)), 1.0);
      }
    `,
    side: THREE.BackSide,
    depthWrite: false
  });
  const skyDome = new THREE.Mesh(skyGeo, skyMat);
  scene.add(skyDome);

  /* ---------- Camera & Renderer ---------- */
  const camera = new THREE.PerspectiveCamera(40, mapWrap.clientWidth / mapWrap.clientHeight, 25, 16000);
  const DEFAULT_POS    = new THREE.Vector3(-120, 1260, 1620);
  const DEFAULT_TARGET = new THREE.Vector3(0, 20, 45);
  const DEFAULT_DIST   = DEFAULT_POS.distanceTo(DEFAULT_TARGET);
  camera.position.copy(DEFAULT_POS);

  const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: false, powerPreference: 'high-performance' });
  renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
  renderer.setSize(mapWrap.clientWidth, mapWrap.clientHeight);
  renderer.shadowMap.enabled = true;
  renderer.shadowMap.type = THREE.PCFSoftShadowMap;
  renderer.outputEncoding = THREE.sRGBEncoding;
  renderer.toneMapping = THREE.ACESFilmicToneMapping;
  renderer.toneMappingExposure = 1.06;
  container.innerHTML = '';
  container.appendChild(renderer.domElement);

  const controls = new THREE.OrbitControls(camera, renderer.domElement);
  controls.target.copy(DEFAULT_TARGET);
  controls.enableDamping = true;
  controls.dampingFactor = 0.075;
  controls.rotateSpeed = 0.72;
  controls.zoomSpeed = 1.15;
  controls.panSpeed = 0.85;
  controls.minDistance = 320;
  controls.maxDistance = 3600;
  controls.maxPolarAngle = Math.PI * 0.468;
  controls.minPolarAngle = 0.06;
  controls.autoRotate = false;
  controls.autoRotateSpeed = 0.65;

  /* ---------- Hệ thống ánh sáng kiến trúc 3 nguồn ---------- */
  const hemiLight = new THREE.HemisphereLight(skyThemes.day.hemiSky, skyThemes.day.hemiGround, skyThemes.day.hemiInt);
  hemiLight.position.set(0, 1800, 0);
  scene.add(hemiLight);

  const dirLight = new THREE.DirectionalLight(skyThemes.day.sunColor, skyThemes.day.sunInt);
  dirLight.position.set(...skyThemes.day.sunPos);
  dirLight.castShadow = true;
  dirLight.shadow.mapSize.width = 2048;
  dirLight.shadow.mapSize.height = 2048;
  dirLight.shadow.camera.near = 200;
  dirLight.shadow.camera.far = 5200;
  const shadowExtent = 1850;
  dirLight.shadow.camera.left = -shadowExtent;
  dirLight.shadow.camera.right = shadowExtent;
  dirLight.shadow.camera.top = shadowExtent;
  dirLight.shadow.camera.bottom = -shadowExtent;
  dirLight.shadow.bias = -0.00045;
  scene.add(dirLight);

  const fillLight = new THREE.DirectionalLight(skyThemes.day.fillColor, skyThemes.day.fillInt);
  fillLight.position.set(-1100, 800, -1000);
  scene.add(fillLight);

  /* ---------- Cảnh quan khuôn viên (Đất, Cỏ, Quảng trường, Đường, Vỉa hè) ---------- */
  const envGroup = new THREE.Group();
  scene.add(envGroup);

  // Vùng đệm đô thị xung quanh trường
  const outerGround = new THREE.Mesh(
    new THREE.PlaneGeometry(24000, 24000),
    new THREE.MeshStandardMaterial({ color: 0xe2e8f0, roughness: 0.95, metalness: 0.0 })
  );
  outerGround.rotation.x = -Math.PI / 2;
  outerGround.position.y = -2;
  outerGround.receiveShadow = true;
  envGroup.add(outerGround);

  // Bệ khuôn viên trường Đại học Quy Nhơn (cỏ xanh dịu mắt)
  function createGrassTexture() {
    const c = document.createElement('canvas');
    c.width = 256; c.height = 256;
    const g = c.getContext('2d');
    g.fillStyle = '#cbe4c1';
    g.fillRect(0, 0, 256, 256);
    g.fillStyle = 'rgba(134, 189, 120, 0.22)';
    for (let i = 0; i < 420; i++) {
      g.fillRect((i * 53) % 256, (i * 97) % 256, 4, 4);
    }
    const tex = new THREE.CanvasTexture(c);
    tex.wrapS = THREE.RepeatWrapping;
    tex.wrapT = THREE.RepeatWrapping;
    tex.repeat.set(16, 10);
    tex.encoding = THREE.sRGBEncoding;
    return tex;
  }

  const campusBase = new THREE.Mesh(
    new THREE.BoxGeometry(MAP_W + 90, 6, MAP_D + 90),
    new THREE.MeshStandardMaterial({ map: createGrassTexture(), roughness: 0.92, metalness: 0.0 })
  );
  campusBase.position.set(0, -2.5, 0);
  campusBase.receiveShadow = true;
  envGroup.add(campusBase);

  // Đường viền tường rào khuôn viên
  const wallMat = new THREE.MeshStandardMaterial({ color: 0x94a3b8, roughness: 0.7 });
  const wallH = 12, wallT = 6;
  const northWall = new THREE.Mesh(new THREE.BoxGeometry(MAP_W + 96, wallH, wallT), wallMat);
  northWall.position.set(0, wallH / 2, -(MAP_D + 90) / 2);
  const westWall = new THREE.Mesh(new THREE.BoxGeometry(wallT, wallH, MAP_D + 96), wallMat);
  westWall.position.set(-(MAP_W + 90) / 2, wallH / 2, 0);
  const eastWall = westWall.clone();
  eastWall.position.x = (MAP_W + 90) / 2;
  [northWall, westWall, eastWall].forEach(w => { w.castShadow = true; w.receiveShadow = true; envGroup.add(w); });

  // Quảng trường & sân lát gạch
  if (typeof CAMPUS_PLAZAS !== 'undefined') {
    CAMPUS_PLAZAS.forEach(([px, py, pw, ph, col]) => {
      const plaza = new THREE.Mesh(
        new THREE.BoxGeometry(pw, 2.2, ph),
        new THREE.MeshStandardMaterial({ color: new THREE.Color(col), roughness: 0.85, metalness: 0.04 })
      );
      plaza.position.set(px + pw / 2 - MAP_W / 2, 1.3, py + ph / 2 - MAP_D / 2);
      plaza.receiveShadow = true;
      envGroup.add(plaza);
    });
  }

  // Sân thể thao ngoài trời (cạnh Tòa nhà Thể chất)
  if (typeof CAMPUS_COURTS !== 'undefined') {
    CAMPUS_COURTS.forEach(ct => {
      const cx = ct.x + ct.w / 2 - MAP_W / 2;
      const cz = ct.y + ct.h / 2 - MAP_D / 2;
      const base = new THREE.Mesh(
        new THREE.BoxGeometry(ct.w + 16, 2.2, ct.h + 16),
        new THREE.MeshStandardMaterial({ color: 0x94a3b8, roughness: 0.85 })
      );
      base.position.set(cx, 1.1, cz);
      base.receiveShadow = true;
      envGroup.add(base);

      const surface = new THREE.Mesh(
        new THREE.BoxGeometry(ct.w, 2.5, ct.h),
        new THREE.MeshStandardMaterial({ color: new THREE.Color(ct.color), roughness: 0.78 })
      );
      surface.position.set(cx, 1.4, cz);
      surface.receiveShadow = true;
      envGroup.add(surface);

      const lineMat = new THREE.MeshBasicMaterial({ color: 0xffffff });
      const midLine = new THREE.Mesh(new THREE.BoxGeometry(3, 2.8, ct.h - 14), lineMat);
      midLine.position.set(cx, 1.5, cz);
      envGroup.add(midLine);

      const centerRing = new THREE.Mesh(new THREE.RingGeometry(18, 21, 24), lineMat);
      centerRing.rotation.x = -Math.PI / 2;
      centerRing.position.set(cx, 2.95, cz);
      envGroup.add(centerRing);
    });
  }

  // Đường nội bộ nhựa xám đậm + vỉa hè + vạch kẻ đường đứt nét
  const sidewalkMat = new THREE.MeshStandardMaterial({ color: 0xdbe2ea, roughness: 0.88 });
  const roadMat = new THREE.MeshStandardMaterial({ color: 0x475569, roughness: 0.82, metalness: 0.05 });
  const dashMat = new THREE.MeshBasicMaterial({ color: 0xf8fafc });

  CAMPUS_ROADS.forEach(([rx, ry, rw, rh]) => {
    const cx = rx + rw / 2 - MAP_W / 2;
    const cz = ry + rh / 2 - MAP_D / 2;

    const sidewalk = new THREE.Mesh(new THREE.BoxGeometry(rw + 10, 1.8, rh + 10), sidewalkMat);
    sidewalk.position.set(cx, 0.9, cz);
    sidewalk.receiveShadow = true;
    envGroup.add(sidewalk);

    const road = new THREE.Mesh(new THREE.BoxGeometry(rw, 2.3, rh), roadMat);
    road.position.set(cx, 1.25, cz);
    road.receiveShadow = true;
    envGroup.add(road);

    const isHoriz = rw > rh;
    const len = isHoriz ? rw : rh;
    const dashLen = 22, gap = 22;
    for (let pos = -len / 2 + 24; pos < len / 2 - 24; pos += dashLen + gap) {
      const dash = new THREE.Mesh(
        new THREE.BoxGeometry(isHoriz ? dashLen : 2.6, 2.6, isHoriz ? 2.6 : dashLen),
        dashMat
      );
      dash.position.set(isHoriz ? cx + pos : cx, 1.4, isHoriz ? cz : cz + pos);
      envGroup.add(dash);
    }
  });

  // Vạch qua đường Zebra Crossing
  if (typeof CAMPUS_CROSSWALKS !== 'undefined') {
    CAMPUS_CROSSWALKS.forEach(([x, y, horiz]) => {
      const cx = x - MAP_W / 2;
      const cz = y - MAP_D / 2;
      for (let i = -2; i <= 2; i++) {
        const stripe = new THREE.Mesh(
          new THREE.BoxGeometry(horiz ? 4.5 : 18, 2.7, horiz ? 18 : 4.5),
          dashMat
        );
        stripe.position.set(cx + (horiz ? i * 7 : 0), 1.45, cz + (horiz ? 0 : i * 7));
        envGroup.add(stripe);
      }
    });
  }

  // Điểm nhấn quảng trường trung tâm: Đài phun nước & Cột cờ Tổ quốc QNU
  const plazaX = 1280 - MAP_W / 2;
  const plazaZ = 960 - MAP_D / 2;

  const fountainRim = new THREE.Mesh(
    new THREE.CylinderGeometry(36, 39, 8, 28),
    new THREE.MeshStandardMaterial({ color: 0xf1f5f9, roughness: 0.6 })
  );
  fountainRim.position.set(plazaX + 75, 5, plazaZ + 12);
  fountainRim.castShadow = true;
  fountainRim.receiveShadow = true;
  envGroup.add(fountainRim);

  const fountainWater = new THREE.Mesh(
    new THREE.CylinderGeometry(31, 31, 6, 24),
    new THREE.MeshStandardMaterial({ color: 0x38bdf8, roughness: 0.15, metalness: 0.35 })
  );
  fountainWater.position.set(plazaX + 75, 6.6, plazaZ + 12);
  envGroup.add(fountainWater);

  const flagBase = new THREE.Mesh(
    new THREE.CylinderGeometry(12, 16, 10, 8),
    new THREE.MeshStandardMaterial({ color: 0xe2e8f0, roughness: 0.5 })
  );
  flagBase.position.set(plazaX - 82, 6, plazaZ + 12);
  flagBase.castShadow = true;
  envGroup.add(flagBase);

  const flagPole = new THREE.Mesh(
    new THREE.CylinderGeometry(1.4, 2.2, 115, 8),
    new THREE.MeshStandardMaterial({ color: 0xf8fafc, metalness: 0.7, roughness: 0.25 })
  );
  flagPole.position.set(plazaX - 82, 66, plazaZ + 12);
  flagPole.castShadow = true;
  envGroup.add(flagPole);

  const flagCloth = new THREE.Mesh(
    new THREE.BoxGeometry(26, 16, 1.2),
    new THREE.MeshStandardMaterial({ color: 0xdc2626, roughness: 0.65 })
  );
  flagCloth.position.set(plazaX - 69, 113, plazaZ + 12);
  flagCloth.castShadow = true;
  envGroup.add(flagCloth);

  /* ---------- Cây xanh 3D tự nhiên (Tán tròn nhiều lớp + Tán tháp) ---------- */
  const trunkMat = new THREE.MeshStandardMaterial({ color: 0x6b4423, roughness: 0.9 });
  const foliageMats = [
    new THREE.MeshStandardMaterial({ color: 0x2f855a, roughness: 0.82 }),
    new THREE.MeshStandardMaterial({ color: 0x38a169, roughness: 0.82 }),
    new THREE.MeshStandardMaterial({ color: 0x48bb78, roughness: 0.80 })
  ];
  const trunkGeo = new THREE.CylinderGeometry(3, 4.8, 18, 7);
  const sphereCrownGeo = new THREE.DodecahedronGeometry(16, 1);
  const coneCrownGeo = new THREE.ConeGeometry(14, 34, 8);

  CAMPUS_TREES.forEach(([tx, ty], idx) => {
    const tree = new THREE.Group();
    const trunk = new THREE.Mesh(trunkGeo, trunkMat);
    trunk.position.y = 9;
    trunk.castShadow = true;
    tree.add(trunk);

    const mat = foliageMats[idx % foliageMats.length];
    if (idx % 3 === 0) {
      const c1 = new THREE.Mesh(coneCrownGeo, mat);
      c1.position.y = 29;
      c1.castShadow = true;
      tree.add(c1);
    } else {
      const c1 = new THREE.Mesh(sphereCrownGeo, mat);
      c1.position.y = 26;
      c1.castShadow = true;
      const c2 = new THREE.Mesh(sphereCrownGeo, foliageMats[(idx + 1) % foliageMats.length]);
      c2.scale.setScalar(0.68);
      c2.position.set(7, 31, 4);
      c2.castShadow = true;
      tree.add(c1, c2);
    }

    const scale = 0.84 + (idx % 4) * 0.12;
    tree.scale.setScalar(scale);
    tree.position.set(tx - MAP_W / 2, 1, ty - MAP_D / 2);
    envGroup.add(tree);
  });

  /* ---------- Đèn đường chiếu sáng ban đêm ---------- */
  const lampBulbMats = [];
  const poleMat = new THREE.MeshStandardMaterial({ color: 0x334155, metalness: 0.65, roughness: 0.35 });
  const poleGeo = new THREE.CylinderGeometry(1.4, 2.2, 42, 6);
  const bulbGeo = new THREE.SphereGeometry(3.4, 8, 8);
  const sharedLampBulbMat = new THREE.MeshStandardMaterial({
    color: 0xfef08a,
    emissive: 0xfacc15,
    emissiveIntensity: 0.0,
    roughness: 0.2
  });
  lampBulbMats.push(sharedLampBulbMat);

  if (typeof CAMPUS_LAMPS !== 'undefined') {
    CAMPUS_LAMPS.forEach(([lx, ly]) => {
      const lamp = new THREE.Group();
      const pole = new THREE.Mesh(poleGeo, poleMat);
      pole.position.y = 21;
      pole.castShadow = true;
      const bulb = new THREE.Mesh(bulbGeo, sharedLampBulbMat);
      bulb.position.y = 43;
      lamp.add(pole, bulb);
      lamp.position.set(lx - MAP_W / 2, 1, ly - MAP_D / 2);
      envGroup.add(lamp);
    });
  }

  /* ---------- Texture Mặt đứng Kiến trúc (Day + Night Emissive) ---------- */
  const texCache = new Map();
  function getFacadeTextures(typeKey, floors, arch) {
    const cacheKey = `${typeKey}_${floors}_${arch}`;
    if (texCache.has(cacheKey)) return texCache.get(cacheKey);

    const t = ZONE_TYPES[typeKey];
    const cw = 256;
    const ch = Math.max(128, Math.min(1024, floors * 44));

    const dayCanvas = document.createElement('canvas');
    dayCanvas.width = cw; dayCanvas.height = ch;
    const ctx = dayCanvas.getContext('2d');

    const nightCanvas = document.createElement('canvas');
    nightCanvas.width = cw; nightCanvas.height = ch;
    const nctx = nightCanvas.getContext('2d');
    nctx.fillStyle = '#000000';
    nctx.fillRect(0, 0, cw, ch);

    // Nền tường kiến trúc
    ctx.fillStyle = arch === 'tower' ? '#dbeafe' : t.wall;
    ctx.fillRect(0, 0, cw, ch);

    const rowH = ch / floors;
    const cols = arch === 'tower' ? 6 : (arch === 'library' ? 5 : 4);
    const padX = arch === 'tower' ? 12 : 16;
    const slotW = (cw - padX * 2) / cols;

    for (let f = 0; f < floors; f++) {
      const y = f * rowH;
      const isGround = (f === floors - 1);

      // Dải phân tầng kiến trúc
      ctx.fillStyle = arch === 'tower' ? '#1e3a8a' : t.accent;
      ctx.globalAlpha = arch === 'tower' ? 0.25 : 0.22;
      ctx.fillRect(0, y, cw, Math.max(3, rowH * 0.14));
      ctx.globalAlpha = 1;

      // Vẽ cửa sổ từng tầng
      for (let c = 0; c < cols; c++) {
        const winW = slotW * (arch === 'tower' || arch === 'lab' ? 0.72 : 0.58);
        const winH = rowH * (isGround ? 0.62 : 0.52);
        const wx = padX + c * slotW + (slotW - winW) / 2;
        const wy = y + rowH * (isGround ? 0.26 : 0.28);

        // Khung cửa sổ
        ctx.fillStyle = 'rgba(15, 23, 42, 0.18)';
        ctx.fillRect(wx - 2, wy - 2, winW + 4, winH + 4);

        // Kính cửa sổ với gradient phản chiếu bầu trời
        const grad = ctx.createLinearGradient(wx, wy, wx, wy + winH);
        if (arch === 'tower' || arch === 'library') {
          grad.addColorStop(0, '#0284c7');
          grad.addColorStop(0.55, '#38bdf8');
          grad.addColorStop(1, '#1e3a8a');
        } else {
          grad.addColorStop(0, t.window);
          grad.addColorStop(1, '#60a5fa');
        }
        ctx.fillStyle = grad;
        ctx.fillRect(wx, wy, winW, winH);

        // Ánh sáng chéo trên kính
        ctx.fillStyle = 'rgba(255,255,255,0.22)';
        ctx.fillRect(wx + 2, wy + 2, winW * 0.32, winH - 4);

        // Đèn ban đêm (tỉ lệ sáng tự nhiên giả ngẫu nhiên theo vị trí)
        const lit = ((f * 7 + c * 13 + floors) % 5) !== 0;
        if (lit) {
          nctx.fillStyle = ((f + c) % 3 === 0) ? '#fde68a' : '#fef3c7';
          nctx.fillRect(wx + 1, wy + 1, winW - 2, winH - 2);
        }
      }
    }

    const mapTex = new THREE.CanvasTexture(dayCanvas);
    mapTex.wrapS = THREE.RepeatWrapping;
    mapTex.wrapT = THREE.ClampToEdgeWrapping;
    mapTex.encoding = THREE.sRGBEncoding;

    const emissiveTex = new THREE.CanvasTexture(nightCanvas);
    emissiveTex.wrapS = THREE.RepeatWrapping;
    emissiveTex.wrapT = THREE.ClampToEdgeWrapping;
    emissiveTex.encoding = THREE.sRGBEncoding;

    const result = { mapTex, emissiveTex };
    texCache.set(cacheKey, result);
    return result;
  }

  /* ---------- Dựng các tòa nhà 3D chuyên nghiệp từ ZONES ---------- */
  const buildingRecords = new Map();
  const pickableMeshes = [];
  const facadeMaterials = [];
  const pulseRings = [];

  ZONES.forEach(z => {
    const t = ZONE_TYPES[z.type];
    const b = bbox(z);
    const h = getBuildingHeight(z);
    const group = new THREE.Group();
    group.userData.zoneId = z.id;

    const { mapTex, emissiveTex } = getFacadeTextures(z.type, z.floors || 3, z.arch);

    const wallMat = new THREE.MeshStandardMaterial({
      color: 0xffffff,
      map: mapTex,
      emissive: 0xffe49e,
      emissiveMap: emissiveTex,
      emissiveIntensity: 0.0,
      roughness: z.arch === 'tower' || z.arch === 'library' ? 0.28 : 0.55,
      metalness: z.arch === 'tower' ? 0.32 : 0.08,
      transparent: true,
      opacity: 1
    });
    facadeMaterials.push(wallMat);

    const topMat = new THREE.MeshStandardMaterial({
      color: new THREE.Color(t.top),
      roughness: 0.65,
      metalness: 0.06,
      transparent: true,
      opacity: 1
    });

    const accentMat = new THREE.MeshStandardMaterial({
      color: new THREE.Color(t.accent),
      roughness: 0.45,
      metalness: 0.16,
      transparent: true,
      opacity: 1
    });

    const roofMat = new THREE.MeshStandardMaterial({
      color: new THREE.Color(t.roof),
      roughness: 0.72,
      metalness: 0.08,
      transparent: true,
      opacity: 1
    });

    const edgeMat = new THREE.LineBasicMaterial({
      color: new THREE.Color(t.stroke),
      transparent: true,
      opacity: 0.35
    });

    const hitMeshes = [];
    const materials = [wallMat, topMat, accentMat, roofMat];
    const edgeLines = [];

    function registerMesh(mesh, addEdges = false) {
      mesh.castShadow = true;
      mesh.receiveShadow = true;
      mesh.userData.zoneId = z.id;
      group.add(mesh);
      hitMeshes.push(mesh);
      pickableMeshes.push(mesh);
      if (addEdges) {
        const line = new THREE.LineSegments(new THREE.EdgesGeometry(mesh.geometry), edgeMat);
        line.position.copy(mesh.position);
        line.rotation.copy(mesh.rotation);
        group.add(line);
        edgeLines.push(line);
      }
    }

    const bx = b.cx - MAP_W / 2;
    const bz = b.cy - MAP_D / 2;

    if (z.arch === 'parking') {
      // Nền bê tông có kẻ vạch đậu xe
      const slab = new THREE.Mesh(new THREE.BoxGeometry(b.w, 4, b.h), topMat);
      slab.position.set(bx, 2.5, bz);
      registerMesh(slab, true);

      // Mái che thép hiện đại
      const roof = new THREE.Mesh(new THREE.BoxGeometry(b.w - 4, 4.5, b.h - 4), accentMat);
      roof.position.set(bx, h, bz);
      registerMesh(roof, true);

      // Cột thép đỡ mái
      const colsX = Math.max(2, Math.floor(b.w / 70) + 1);
      const colsZ = Math.max(2, Math.floor(b.h / 95) + 1);
      const postGeo = new THREE.CylinderGeometry(2.2, 2.2, h - 2, 8);
      const postMat = new THREE.MeshStandardMaterial({ color: 0x475569, metalness: 0.5, roughness: 0.4 });
      for (let ix = 0; ix < colsX; ix++) {
        for (let iz = 0; iz < colsZ; iz++) {
          const px = b.x + 10 + (colsX > 1 ? (ix / (colsX - 1)) * (b.w - 20) : (b.w - 20) / 2);
          const pz = b.y + 10 + (colsZ > 1 ? (iz / (colsZ - 1)) * (b.h - 20) : (b.h - 20) / 2);
          const post = new THREE.Mesh(postGeo, postMat);
          post.position.set(px - MAP_W / 2, h / 2, pz - MAP_D / 2);
          post.castShadow = true;
          group.add(post);
        }
      }
    } else if (z.poly) {
      // Tòa nhà 4T hình chữ L/đa giác
      const pts = z.poly.split(' ').map(p => p.split(',').map(Number));
      const shape = new THREE.Shape();
      pts.forEach(([px, py], idx) => {
        const sx = px - MAP_W / 2;
        const sy = -(py - MAP_D / 2);
        if (idx === 0) shape.moveTo(sx, sy);
        else shape.lineTo(sx, sy);
      });
      shape.closePath();

      const extrudeGeo = new THREE.ExtrudeGeometry(shape, { depth: h, bevelEnabled: false });
      const polyMesh = new THREE.Mesh(extrudeGeo, [topMat, wallMat]);
      polyMesh.rotation.x = -Math.PI / 2;
      polyMesh.position.y = 2;
      registerMesh(polyMesh, true);

      const capGeo = new THREE.ExtrudeGeometry(shape, { depth: 6, bevelEnabled: false });
      const capMesh = new THREE.Mesh(capGeo, accentMat);
      capMesh.rotation.x = -Math.PI / 2;
      capMesh.position.y = h + 2;
      registerMesh(capMesh, false);
    } else {
      // Bệ móng (Plinth)
      const plinth = new THREE.Mesh(new THREE.BoxGeometry(b.w + 10, 6, b.h + 10), roofMat);
      plinth.position.set(bx, 3.5, bz);
      registerMesh(plinth, false);

      // Khối thân tòa nhà chính
      const bodyGeo = new THREE.BoxGeometry(b.w, h, b.h);
      const bodyMesh = new THREE.Mesh(bodyGeo, [wallMat, wallMat, topMat, topMat, wallMat, wallMat]);
      bodyMesh.position.set(bx, h / 2 + 5, bz);
      registerMesh(bodyMesh, true);

      // Viền mái (Parapet / Cornice)
      const cornice = new THREE.Mesh(new THREE.BoxGeometry(b.w + 7, 7, b.h + 7), accentMat);
      cornice.position.set(bx, h + 7, bz);
      registerMesh(cornice, false);

      // Kiến trúc đặc trưng theo từng loại công trình
      if (z.arch === 'tower') {
        // Tòa nhà 15T biểu tượng QNU: Khối đế + Tháp kính + Mái kỹ thuật + Ăng-ten + Đèn chớp đỏ
        const podium = new THREE.Mesh(new THREE.BoxGeometry(b.w + 22, 34, b.h + 22), topMat);
        podium.position.set(bx, 18, bz);
        registerMesh(podium, false);

        const crown1 = new THREE.Mesh(new THREE.BoxGeometry(b.w * 0.72, 24, b.h * 0.72), topMat);
        crown1.position.set(bx, h + 22, bz);
        registerMesh(crown1, true);

        const crown2 = new THREE.Mesh(new THREE.BoxGeometry(b.w * 0.44, 18, b.h * 0.44), accentMat);
        crown2.position.set(bx, h + 42, bz);
        registerMesh(crown2, false);

        const spire = new THREE.Mesh(
          new THREE.CylinderGeometry(1.6, 4.2, 48, 8),
          new THREE.MeshStandardMaterial({ color: 0xe2e8f0, metalness: 0.75, roughness: 0.2 })
        );
        spire.position.set(bx, h + 74, bz);
        group.add(spire);

        const beacon = new THREE.Mesh(
          new THREE.SphereGeometry(4.5, 10, 10),
          new THREE.MeshStandardMaterial({ color: 0xff2d2d, emissive: 0xff0000, emissiveIntensity: 1.4 })
        );
        beacon.position.set(bx, h + 99, bz);
        beacon.userData.isBeacon = true;
        group.add(beacon);
      } else if (z.arch === 'sports') {
        // Tòa nhà Thể chất: Mái vòm cong kiến trúc (Barrel Vault)
        const radius = b.h * 0.46;
        const vaultGeo = new THREE.CylinderGeometry(radius, radius, b.w - 6, 20, 1, false, 0, Math.PI);
        const vault = new THREE.Mesh(vaultGeo, topMat);
        vault.rotation.z = Math.PI / 2;
        vault.rotation.y = Math.PI / 2;
        vault.scale.set(1, 1, 0.45);
        vault.position.set(bx, h + 10, bz);
        registerMesh(vault, false);
      } else if (z.arch === 'library') {
        // Thư viện trường: Giếng trời kính (Skylight) trên mái
        const skylight = new THREE.Mesh(
          new THREE.BoxGeometry(b.w * 0.56, 16, b.h * 0.56),
          new THREE.MeshStandardMaterial({ color: 0x38bdf8, roughness: 0.15, metalness: 0.4, transparent: true, opacity: 0.88 })
        );
        skylight.position.set(bx, h + 16, bz);
        registerMesh(skylight, true);
      } else if (z.arch === 'hall') {
        // Hội trường A & B: Mái giật cấp + Sảnh đón cột trụ
        const stageCap = new THREE.Mesh(new THREE.BoxGeometry(b.w * 0.72, 16, b.h * 0.65), topMat);
        stageCap.position.set(bx, h + 16, bz);
        registerMesh(stageCap, false);

        const canopy = new THREE.Mesh(new THREE.BoxGeometry(b.w * 0.5, 5, 22), accentMat);
        canopy.position.set(bx, 22, bz + b.h / 2 + 10);
        group.add(canopy);
      } else if (z.arch === 'dorm') {
        // Ký túc xá: Cụm bồn nước inox trên sân thượng + tum cầu thang
        const pent = new THREE.Mesh(new THREE.BoxGeometry(b.w * 0.4, 14, b.h * 0.25), topMat);
        pent.position.set(bx, h + 15, bz - b.h * 0.15);
        registerMesh(pent, false);

        const tankMat = new THREE.MeshStandardMaterial({ color: 0xcbd5e1, metalness: 0.85, roughness: 0.2 });
        for (let k = -1; k <= 1; k++) {
          const tank = new THREE.Mesh(new THREE.CylinderGeometry(7, 7, 14, 12), tankMat);
          tank.position.set(bx + k * 20, h + 16, bz + b.h * 0.22);
          group.add(tank);
        }
      } else if (z.arch === 'lab') {
        // Khu thực hành: Cụm điều hòa / HVAC trên mái
        const hvacMat = new THREE.MeshStandardMaterial({ color: 0x94a3b8, roughness: 0.55, metalness: 0.35 });
        const unit1 = new THREE.Mesh(new THREE.BoxGeometry(28, 12, 22), hvacMat);
        unit1.position.set(bx - b.w * 0.18, h + 14, bz);
        const unit2 = new THREE.Mesh(new THREE.BoxGeometry(22, 10, 18), hvacMat);
        unit2.position.set(bx + b.w * 0.18, h + 13, bz);
        group.add(unit1, unit2);
      } else if (z.arch === 'academic') {
        // Giảng đường lý thuyết: Tum mái + mái hiên sảnh
        const pent = new THREE.Mesh(new THREE.BoxGeometry(Math.min(b.w * 0.5, 64), 14, Math.min(b.h * 0.22, 64)), topMat);
        pent.position.set(bx, h + 15, bz);
        registerMesh(pent, false);
      } else if (z.arch === 'canteen') {
        // Căn tin: Mái dốc nhẹ
        const cap = new THREE.Mesh(new THREE.ConeGeometry(Math.min(b.w, b.h) * 0.62, 22, 4), accentMat);
        cap.rotation.y = Math.PI / 4;
        cap.position.set(bx, h + 19, bz);
        registerMesh(cap, false);
      }
    }

    // Vòng sóng Radar 3D cảnh báo sự cố (ẩn mặc định, bật khi tòa nhà có phiếu đang xử lý)
    const pulseRadius = Math.max(26, Math.min(b.w, b.h) * 0.34);
    const pulseMat = new THREE.MeshBasicMaterial({
      color: 0xef4444,
      transparent: true,
      opacity: 0.0,
      side: THREE.DoubleSide,
      depthWrite: false
    });
    const pulseRing = new THREE.Mesh(new THREE.RingGeometry(pulseRadius * 0.72, pulseRadius, 32), pulseMat);
    pulseRing.rotation.x = -Math.PI / 2;
    pulseRing.position.set(bx, h + (z.arch === 'tower' ? 56 : 20), bz);
    pulseRing.visible = false;
    group.add(pulseRing);
    pulseRings.push({ zoneId: z.id, mesh: pulseRing, mat: pulseMat });

    scene.add(group);

    // Nhãn HTML nổi bám theo đỉnh tòa nhà
    const labelEl = document.createElement('button');
    labelEl.type = 'button';
    labelEl.className = 'zone-label';
    labelEl.style.borderColor = t.stroke;
    labelEl.setAttribute('aria-label', 'Chọn khu vực ' + z.name.replace(/\n/g, ' '));
    labelEl.innerHTML = `<span class="lbl-name">${escapeHtml(z.name)}</span>`;
    labelEl.addEventListener('click', e => {
      e.stopPropagation();
      openZone(z.id);
    });
    labelEl.addEventListener('mouseenter', () => setHoverZone(z.id, null));
    labelEl.addEventListener('mouseleave', () => setHoverZone(null, null));
    labelsBox.appendChild(labelEl);

    buildingRecords.set(z.id, {
      zone: z,
      group,
      hitMeshes,
      materials,
      edgeLines,
      edgeMat,
      targetY: 0,
      anchor3D: new THREE.Vector3(bx, h + (z.arch === 'tower' ? 92 : 24), bz),
      labelEl,
      pulseRing
    });
  });

  /* ---------- Dựng Cổng trường QNU 3D Kiến trúc Hiện đại ---------- */
  const gateRecords = [];
  const gatePillarMat = new THREE.MeshStandardMaterial({ color: 0xf8fafc, roughness: 0.45, metalness: 0.1 });
  const gateBeamMat   = new THREE.MeshStandardMaterial({ color: 0x1e3a8a, roughness: 0.35, metalness: 0.25 });
  const gateLedMat    = new THREE.MeshStandardMaterial({ color: 0x38bdf8, emissive: 0x0ea5e9, emissiveIntensity: 0.8 });

  GATES.forEach(gt => {
    const [gx, gy, gw, gh] = gt.rect;
    const cx = gx + gw / 2 - MAP_W / 2;
    const cz = gy + gh / 2 - MAP_D / 2;

    const gateGroup = new THREE.Group();
    const p1 = new THREE.Mesh(new THREE.BoxGeometry(18, 56, gh * 0.78), gatePillarMat);
    p1.position.set(cx - gw / 2 + 16, 28, cz);
    const p2 = p1.clone();
    p2.position.set(cx + gw / 2 - 16, 28, cz);

    const beam = new THREE.Mesh(new THREE.BoxGeometry(gw + 16, 15, gh * 0.92), gateBeamMat);
    beam.position.set(cx, 54, cz);

    const ledStrip = new THREE.Mesh(new THREE.BoxGeometry(gw + 6, 3, gh * 0.96), gateLedMat);
    ledStrip.position.set(cx, 46, cz);

    [p1, p2, beam].forEach(m => { m.castShadow = true; m.receiveShadow = true; gateGroup.add(m); });
    gateGroup.add(ledStrip);
    scene.add(gateGroup);

    const gLabel = document.createElement('div');
    gLabel.className = 'gate-label';
    gLabel.textContent = gt.name;
    labelsBox.appendChild(gLabel);

    gateRecords.push({
      anchor3D: new THREE.Vector3(cx, 72, cz),
      labelEl: gLabel
    });
  });

  /* ---------- Chuyển đổi Chế độ Thời gian (Sáng / Hoàng hôn / Đêm) ---------- */
  function setTheme(themeKey) {
    const th = skyThemes[themeKey] || skyThemes.day;
    currentTheme = themeKey;

    skyUniforms.topColor.value.setHex(th.top);
    skyUniforms.bottomColor.value.setHex(th.horizon);
    scene.fog.color.setHex(th.fog);
    scene.fog.density = th.fogDensity;

    hemiLight.color.setHex(th.hemiSky);
    hemiLight.groundColor.setHex(th.hemiGround);
    hemiLight.intensity = th.hemiInt;

    dirLight.color.setHex(th.sunColor);
    dirLight.intensity = th.sunInt;
    dirLight.position.set(...th.sunPos);

    fillLight.color.setHex(th.fillColor);
    fillLight.intensity = th.fillInt;

    facadeMaterials.forEach(m => { m.emissiveIntensity = th.winGlow; });
    lampBulbMats.forEach(m => { m.emissiveIntensity = th.lampGlow; });

    document.querySelectorAll('[data-theme]').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.theme === themeKey);
    });
  }

  /* ---------- Hiệu ứng chuyển động Camera mượt mà & Presets ---------- */
  let camAnim = null;
  let isTopDown = false;

  function animateCameraTo(endPos, endTarget, duration = 700) {
    camAnim = {
      startPos: camera.position.clone(),
      endPos: endPos.clone(),
      startTarget: controls.target.clone(),
      endTarget: endTarget.clone(),
      startTime: performance.now(),
      duration
    };
  }

  function zoomByFactor(factor) {
    const offset = camera.position.clone().sub(controls.target);
    const currentDist = offset.length();
    const nextDist = THREE.MathUtils.clamp(currentDist * factor, controls.minDistance, controls.maxDistance);
    offset.setLength(nextDist);
    animateCameraTo(controls.target.clone().add(offset), controls.target.clone(), 240);
  }

  function resetView() {
    isTopDown = false;
    $('#btn-view-mode').textContent = '2D';
    $('#btn-view-mode').classList.remove('is-on');
    animateCameraTo(DEFAULT_POS, DEFAULT_TARGET, 650);
    setActivePresetBtn('overview');
  }

  function toggleViewMode() {
    isTopDown = !isTopDown;
    const btn = $('#btn-view-mode');
    btn.textContent = isTopDown ? '3D' : '2D';
    btn.classList.toggle('is-on', isTopDown);
    if (isTopDown) {
      animateCameraTo(new THREE.Vector3(0, 2250, 90), new THREE.Vector3(0, 0, 20), 650);
    } else {
      animateCameraTo(DEFAULT_POS, DEFAULT_TARGET, 650);
    }
  }

  function setActivePresetBtn(key) {
    document.querySelectorAll('[data-preset]').forEach(b => {
      b.classList.toggle('active', b.dataset.preset === key);
    });
  }

  function goToPreset(preset) {
    isTopDown = false;
    $('#btn-view-mode').textContent = '2D';
    $('#btn-view-mode').classList.remove('is-on');
    setActivePresetBtn(preset);
    if (preset === 'overview') {
      animateCameraTo(DEFAULT_POS, DEFAULT_TARGET, 700);
    } else if (preset === 'tower') {
      const rec = buildingRecords.get('15T');
      if (rec) {
        const t = new THREE.Vector3(rec.anchor3D.x, 120, rec.anchor3D.z);
        animateCameraTo(new THREE.Vector3(t.x + 360, 440, t.z + 520), t, 750);
      }
    } else if (preset === 'gate') {
      const t = new THREE.Vector3(0, 30, MAP_D / 2 - 60);
      animateCameraTo(new THREE.Vector3(-240, 320, MAP_D / 2 + 540), t, 750);
    }
  }

  function focusZone(zoneId) {
    const rec = buildingRecords.get(zoneId);
    if (!rec) return;
    const target = new THREE.Vector3(rec.anchor3D.x, rec.anchor3D.y * 0.45, rec.anchor3D.z);
    const pos = new THREE.Vector3(target.x + 280, Math.max(430, rec.anchor3D.y + 340), target.z + 500);
    animateCameraTo(pos, target, 700);
  }

  /* ---------- Raycasting: Hover & Click chọn tòa nhà 3D ---------- */
  const raycaster = new THREE.Raycaster();
  const pointer = new THREE.Vector2();
  let downX = 0, downY = 0, isPointerDown = false;

  function updatePointer(e) {
    const rect = renderer.domElement.getBoundingClientRect();
    pointer.x = ((e.clientX - rect.left) / rect.width) * 2 - 1;
    pointer.y = -((e.clientY - rect.top) / rect.height) * 2 + 1;
  }

  function setHoverZone(zoneId, clientEvent) {
    if (zoneId && hiddenTypes.has(ZONES.find(x => x.id === zoneId)?.type)) {
      zoneId = null;
    }
    if (hoveredZoneId !== zoneId) {
      hoveredZoneId = zoneId;
      mapWrap.classList.toggle('is-hovering', Boolean(zoneId));
      buildingRecords.forEach((rec, id) => {
        const isHov = id === hoveredZoneId;
        const isAct = currentZone?.id === id;
        rec.labelEl.classList.toggle('hovered', isHov);
        rec.targetY = isHov ? 10 : (isAct ? 6 : 0);
        rec.edgeMat.opacity = isHov || isAct ? 0.95 : 0.35;
      });
    }

    if (zoneId && clientEvent) {
      const z = buildingRecords.get(zoneId).zone;
      const cnt = reportsByZone[zoneId] || 0;
      const r = mapWrap.getBoundingClientRect();
      tooltip.innerHTML = `<b>${escapeHtml(z.name.replace(/\n/g, ' '))}</b>` +
        (cnt > 0 ? ` · <span style="color:#fca5a5">${cnt} phiếu liên quan</span>` : (window.CAMPUS_IS_STAFF ? ' · Nhấp để xem danh sách phiếu tại tòa nhà' : ' · Nhấp để chọn phòng &amp; gửi yêu cầu'));
      tooltip.style.left = (clientEvent.clientX - r.left) + 'px';
      tooltip.style.top = (clientEvent.clientY - r.top) + 'px';
      tooltip.hidden = false;
    } else {
      tooltip.hidden = true;
    }
  }

  renderer.domElement.addEventListener('pointerdown', e => {
    isPointerDown = true;
    downX = e.clientX;
    downY = e.clientY;
    camAnim = null;
    mapWrap.classList.add('is-grabbing');
  });

  window.addEventListener('pointerup', () => {
    isPointerDown = false;
    mapWrap.classList.remove('is-grabbing');
  });

  renderer.domElement.addEventListener('pointermove', e => {
    if (isPointerDown) {
      tooltip.hidden = true;
      return;
    }
    updatePointer(e);
    raycaster.setFromCamera(pointer, camera);
    const activeMeshes = pickableMeshes.filter(m => !hiddenTypes.has(buildingRecords.get(m.userData.zoneId)?.zone.type));
    const hits = raycaster.intersectObjects(activeMeshes, false);
    const hitId = hits.length ? hits[0].object.userData.zoneId : null;
    setHoverZone(hitId, e);
  });

  renderer.domElement.addEventListener('pointerleave', () => setHoverZone(null, null));

  renderer.domElement.addEventListener('click', e => {
    const moved = Math.hypot(e.clientX - downX, e.clientY - downY);
    if (moved > 6) return;
    updatePointer(e);
    raycaster.setFromCamera(pointer, camera);
    const activeMeshes = pickableMeshes.filter(m => !hiddenTypes.has(buildingRecords.get(m.userData.zoneId)?.zone.type));
    const hits = raycaster.intersectObjects(activeMeshes, false);
    if (hits.length) {
      const id = hits[0].object.userData.zoneId;
      focusZone(id);
      openZone(id);
    }
  });

  /* ---------- Cập nhật nhãn 2D & La bàn theo camera mỗi khung hình ---------- */
  const projVec = new THREE.Vector3();
  const compassNeedle = $('#compass-needle');
  let lastZoomPct = -1;

  function syncLabelsAndHUD() {
    const w = mapWrap.clientWidth;
    const h = mapWrap.clientHeight;
    const dist = camera.position.distanceTo(controls.target);

    const zoomPct = Math.round((DEFAULT_DIST / dist) * 100);
    if (zoomPct !== lastZoomPct) {
      lastZoomPct = zoomPct;
      const zl = $('#zoom-level');
      if (zl) zl.textContent = zoomPct + '%';
    }

    if (compassNeedle) {
      const azim = Math.atan2(camera.position.x - controls.target.x, camera.position.z - controls.target.z);
      const deg = (azim * 180) / Math.PI;
      compassNeedle.style.transform = `rotate(${deg.toFixed(1)}deg)`;
    }

    const compactMode = dist > 2350;

    buildingRecords.forEach(rec => {
      const isDim = hiddenTypes.has(rec.zone.type);
      rec.labelEl.classList.toggle('dim', isDim);
      rec.labelEl.classList.toggle('compact', compactMode);

      projVec.copy(rec.anchor3D);
      projVec.y += rec.group.position.y;
      projVec.project(camera);

      if (projVec.z > 1 || projVec.x < -1.12 || projVec.x > 1.12 || projVec.y < -1.12 || projVec.y > 1.12) {
        rec.labelEl.style.display = 'none';
        return;
      }
      rec.labelEl.style.display = '';
      rec.labelEl.style.left = ((projVec.x * 0.5 + 0.5) * w).toFixed(1) + 'px';
      rec.labelEl.style.top = ((-projVec.y * 0.5 + 0.5) * h).toFixed(1) + 'px';
    });

    gateRecords.forEach(g => {
      projVec.copy(g.anchor3D);
      projVec.project(camera);
      if (projVec.z > 1 || dist > 2650) {
        g.labelEl.style.display = 'none';
        return;
      }
      g.labelEl.style.display = '';
      g.labelEl.style.left = ((projVec.x * 0.5 + 0.5) * w).toFixed(1) + 'px';
      g.labelEl.style.top = ((-projVec.y * 0.5 + 0.5) * h).toFixed(1) + 'px';
    });
  }

  /* ---------- Vòng lặp Render 60fps ---------- */
  function animate(now) {
    requestAnimationFrame(animate);

    if (camAnim) {
      const t = Math.min(1, (now - camAnim.startTime) / camAnim.duration);
      const ease = 1 - Math.pow(1 - t, 3);
      camera.position.lerpVectors(camAnim.startPos, camAnim.endPos, ease);
      controls.target.lerpVectors(camAnim.startTarget, camAnim.endTarget, ease);
      if (t >= 1) camAnim = null;
    }

    // Hover lift mượt mà cho các tòa nhà
    buildingRecords.forEach(rec => {
      rec.group.position.y += (rec.targetY - rec.group.position.y) * 0.16;
    });

    // Hiệu ứng sóng Radar 3D cho tòa nhà đang có sự cố
    const wave = (now * 0.0012) % 1;
    pulseRings.forEach(p => {
      if (p.mesh.visible) {
        const s = 0.85 + wave * 1.15;
        p.mesh.scale.set(s, s, 1);
        p.mat.opacity = (1 - wave) * 0.72;
      }
    });

    controls.update();
    renderer.render(scene, camera);
    syncLabelsAndHUD();
  }
  requestAnimationFrame(animate);

  /* ---------- Resize ---------- */
  const ro = new ResizeObserver(() => {
    const w = mapWrap.clientWidth;
    const h = mapWrap.clientHeight;
    if (!w || !h) return;
    camera.aspect = w / h;
    camera.updateProjectionMatrix();
    renderer.setSize(w, h);
  });
  ro.observe(mapWrap);

  return {
    zoomIn: () => zoomByFactor(0.78),
    zoomOut: () => zoomByFactor(1.28),
    resetView,
    toggleViewMode,
    goToPreset,
    setTheme,
    toggleAutoRotate: () => {
      controls.autoRotate = !controls.autoRotate;
      $('#btn-auto-rotate').classList.toggle('is-on', controls.autoRotate);
    },
    toggleLabels: () => {
      labelsBox.classList.toggle('labels-hidden');
      $('#btn-toggle-labels').classList.toggle('is-on', labelsBox.classList.contains('labels-hidden'));
    },
    focusZone,
    setActiveZone: (zoneId) => {
      buildingRecords.forEach((rec, id) => {
        const isAct = id === zoneId;
        rec.labelEl.classList.toggle('active', isAct);
        rec.targetY = isAct ? 8 : 0;
        rec.edgeMat.opacity = isAct ? 0.95 : 0.35;
      });
    },
    applyFilter: () => {
      buildingRecords.forEach(rec => {
        const isDim = hiddenTypes.has(rec.zone.type);
        rec.materials.forEach(m => { m.opacity = isDim ? 0.14 : 1; });
        rec.edgeMat.opacity = isDim ? 0.08 : 0.35;
      });
    },
    updateBadges: () => {
      buildingRecords.forEach((rec, id) => {
        const oldBadge = rec.labelEl.querySelector('.zone-badge');
        if (oldBadge) oldBadge.remove();
        const count = reportsByZone[id] || 0;
        rec.labelEl.classList.toggle('has-badge', count > 0);
        if (rec.pulseRing) rec.pulseRing.visible = count > 0;
        if (count > 0) {
          const badge = document.createElement('span');
          badge.className = 'zone-badge';
          badge.textContent = count > 99 ? '99+' : String(count);
          rec.labelEl.appendChild(badge);
        }
      });
    }
  };
}

/* =====================================================================
 * ENGINE 2: FALLBACK CANVAS 2.5D ISOMETRIC (KHI CHẠY OFFLINE KHÔNG CDN)
 * ===================================================================== */
function initFallbackIsoMap() {
  const canvas = document.createElement('canvas');
  container.innerHTML = '';
  container.appendChild(canvas);
  const ctx = canvas.getContext('2d');

  let scale = 0.42;
  let panX = 0;
  let panY = 30;
  let activeId = null;
  let dragging = false, startX = 0, startY = 0;

  function resize() {
    canvas.width = mapWrap.clientWidth * (window.devicePixelRatio || 1);
    canvas.height = mapWrap.clientHeight * (window.devicePixelRatio || 1);
    draw();
  }

  function toScreen(x, y, z = 0) {
    const cx = x - CAMPUS_SIZE.width / 2;
    const cy = y - CAMPUS_SIZE.depth / 2;
    const isoX = (cx - cy) * 0.68;
    const isoY = (cx + cy) * 0.36 - z;
    return [
      canvas.width / 2 + panX + isoX * scale,
      canvas.height / 2 + panY + isoY * scale
    ];
  }

  function draw() {
    const bgGrad = ctx.createLinearGradient(0, 0, 0, canvas.height);
    bgGrad.addColorStop(0, '#bae6fd');
    bgGrad.addColorStop(1, '#f0f9ff');
    ctx.fillStyle = bgGrad;
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    const zl = $('#zoom-level');
    if (zl) zl.textContent = Math.round((scale / 0.42) * 100) + '%';

    // Nền sân khuôn viên xanh dịu
    const g00 = toScreen(0, 0, 0), g10 = toScreen(CAMPUS_SIZE.width, 0, 0);
    const g11 = toScreen(CAMPUS_SIZE.width, CAMPUS_SIZE.depth, 0), g01 = toScreen(0, CAMPUS_SIZE.depth, 0);
    ctx.beginPath();
    ctx.moveTo(...g00); ctx.lineTo(...g10); ctx.lineTo(...g11); ctx.lineTo(...g01);
    ctx.closePath();
    ctx.fillStyle = '#d1e7c9'; ctx.fill();
    ctx.strokeStyle = '#94a3b8'; ctx.lineWidth = 2; ctx.stroke();

    const sorted = [...ZONES].sort((a, b) => {
      const ba = bbox(a), bb = bbox(b);
      return (ba.cx + ba.cy) - (bb.cx + bb.cy);
    });

    sorted.forEach(z => {
      const b = bbox(z);
      const t = ZONE_TYPES[z.type];
      const h = getBuildingHeight(z);
      const dim = hiddenTypes.has(z.type);

      ctx.save();
      ctx.globalAlpha = dim ? 0.18 : 1;

      const p00 = toScreen(b.x, b.y, 0);
      const p10 = toScreen(b.x + b.w, b.y, 0);
      const p11 = toScreen(b.x + b.w, b.y + b.h, 0);
      const p01 = toScreen(b.x, b.y + b.h, 0);

      const t00 = toScreen(b.x, b.y, h);
      const t10 = toScreen(b.x + b.w, b.y, h);
      const t11 = toScreen(b.x + b.w, b.y + b.h, h);
      const t01 = toScreen(b.x, b.y + b.h, h);

      ctx.beginPath();
      ctx.moveTo(...p10); ctx.lineTo(...p11); ctx.lineTo(...t11); ctx.lineTo(...t10);
      ctx.closePath();
      ctx.fillStyle = t.roof; ctx.fill(); ctx.strokeStyle = t.stroke; ctx.stroke();

      ctx.beginPath();
      ctx.moveTo(...p01); ctx.lineTo(...p11); ctx.lineTo(...t11); ctx.lineTo(...t01);
      ctx.closePath();
      ctx.fillStyle = t.fill; ctx.fill(); ctx.stroke();

      ctx.beginPath();
      ctx.moveTo(...t00); ctx.lineTo(...t10); ctx.lineTo(...t11); ctx.lineTo(...t01);
      ctx.closePath();
      ctx.fillStyle = activeId === z.id ? '#bfdbfe' : t.top;
      ctx.fill();
      ctx.lineWidth = activeId === z.id ? 3 : 1.5;
      ctx.stroke();

      const [lx, ly] = toScreen(b.cx, b.cy, h + 12);
      ctx.fillStyle = '#0f172a';
      ctx.font = `bold ${Math.max(10, Math.round(13 * scale * 2.2))}px "Plus Jakarta Sans", sans-serif`;
      ctx.textAlign = 'center';
      ctx.fillText(z.name.replace(/\n/g, ' '), lx, ly);

      const cnt = reportsByZone[z.id];
      if (cnt > 0) {
        ctx.beginPath();
        ctx.arc(lx, ly - 18, 12, 0, Math.PI * 2);
        ctx.fillStyle = '#dc2626'; ctx.fill();
        ctx.fillStyle = '#fff';
        ctx.font = 'bold 11px sans-serif';
        ctx.fillText(cnt > 99 ? '99+' : String(cnt), lx, ly - 14);
      }
      ctx.restore();
    });
  }

  canvas.addEventListener('wheel', e => {
    e.preventDefault();
    scale = Math.min(1.6, Math.max(0.2, scale * (e.deltaY < 0 ? 1.12 : 0.88)));
    draw();
  }, { passive: false });

  canvas.addEventListener('pointerdown', e => {
    dragging = true; startX = e.clientX; startY = e.clientY;
  });
  window.addEventListener('pointerup', e => {
    if (!dragging) return;
    const moved = Math.hypot(e.clientX - startX, e.clientY - startY);
    dragging = false;
    if (moved < 6) {
      const rect = canvas.getBoundingClientRect();
      const dpr = window.devicePixelRatio || 1;
      const mx = (e.clientX - rect.left) * dpr;
      const my = (e.clientY - rect.top) * dpr;
      let hit = null;
      ZONES.forEach(z => {
        if (hiddenTypes.has(z.type)) return;
        const b = bbox(z);
        const [sx, sy] = toScreen(b.cx, b.cy, getBuildingHeight(z) / 2);
        if (Math.hypot(mx - sx, my - sy) < Math.max(b.w, b.h) * scale * 0.45) hit = z.id;
      });
      if (hit) openZone(hit);
    }
  });
  canvas.addEventListener('pointermove', e => {
    if (!dragging) return;
    const dpr = window.devicePixelRatio || 1;
    panX += (e.clientX - startX) * dpr;
    panY += (e.clientY - startY) * dpr;
    startX = e.clientX; startY = e.clientY;
    draw();
  });

  window.addEventListener('resize', resize);
  resize();

  return {
    zoomIn: () => { scale = Math.min(1.6, scale * 1.2); draw(); },
    zoomOut: () => { scale = Math.max(0.2, scale * 0.82); draw(); },
    resetView: () => { scale = 0.42; panX = 0; panY = 30; draw(); },
    toggleViewMode: () => toast('Chế độ offline đang hiển thị góc nhìn 2.5D Isometric.'),
    toggleAutoRotate: () => toast('Cần kết nối mạng để tải Three.js xoay 360°.'),
    toggleLabels: () => {},
    goToPreset: () => {},
    setTheme: () => toast('Chế độ ánh sáng 3D yêu cầu kết nối mạng để tải Three.js.'),
    focusZone: (id) => {
      const z = ZONES.find(x => x.id === id);
      if (!z) return;
      const b = bbox(z);
      scale = 0.72;
      const [sx, sy] = toScreen(b.cx, b.cy, 0);
      panX += canvas.width / 2 - sx;
      panY += canvas.height / 2 - sy;
      draw();
    },
    setActiveZone: (id) => { activeId = id; draw(); },
    applyFilter: draw,
    updateBadges: draw
  };
}

/* ---------- Cập nhật số liệu & huy hiệu đỏ ---------- */
function updateBadges() {
  if (mapController) mapController.updateBadges();
  const total = Object.values(reportsByZone).reduce((a, b) => a + b, 0);
  const zones = Object.keys(reportsByZone).length;
  ['#stat-total', '#hud-stat-total'].forEach(sel => {
    const el = $(sel);
    if (el) el.textContent = total;
  });
  ['#stat-zones', '#hud-stat-zones'].forEach(sel => {
    const el = $(sel);
    if (el) el.textContent = zones;
  });
}

/* ---------- Chú thích & Bộ lọc nhanh (Sidebar + Floating Pills) ---------- */
function syncFilterUI() {
  const ul = $('#legend');
  if (ul) {
    ul.querySelectorAll('li').forEach(li => {
      li.classList.toggle('off', hiddenTypes.has(li.dataset.type));
    });
  }
  const bar = $('#map-filter-bar');
  if (bar) {
    const allActive = hiddenTypes.size === 0;
    bar.querySelectorAll('.map-pill').forEach(btn => {
      const f = btn.dataset.filter;
      if (f === 'all') {
        btn.classList.toggle('active', allActive);
      } else {
        const isOnlyThis = !allActive && !hiddenTypes.has(f) && hiddenTypes.size === Object.keys(ZONE_TYPES).length - 1;
        btn.classList.toggle('active', isOnlyThis);
      }
    });
  }
}

function renderLegend() {
  const ul = $('#legend');
  if (ul) {
    ul.innerHTML = '';
    Object.entries(ZONE_TYPES).forEach(([key, t]) => {
      const li = document.createElement('li');
      li.dataset.type = key;
      li.innerHTML = `<i style="background:${t.fill};border-color:${t.stroke}"></i>${t.label}`;
      li.onclick = () => {
        hiddenTypes.has(key) ? hiddenTypes.delete(key) : hiddenTypes.add(key);
        syncFilterUI();
        if (mapController) mapController.applyFilter();
      };
      ul.appendChild(li);
    });
  }

  const btnShowAll = $('#btn-show-all');
  if (btnShowAll) {
    btnShowAll.onclick = () => {
      hiddenTypes.clear();
      syncFilterUI();
      if (mapController) mapController.applyFilter();
    };
  }

  const bar = $('#map-filter-bar');
  if (bar) {
    bar.querySelectorAll('.map-pill').forEach(btn => {
      btn.addEventListener('click', () => {
        const f = btn.dataset.filter;
        hiddenTypes.clear();
        if (f !== 'all') {
          Object.keys(ZONE_TYPES).forEach(k => {
            if (k !== f) hiddenTypes.add(k);
          });
        }
        syncFilterUI();
        if (mapController) mapController.applyFilter();
      });
    });
  }
}

/* ---------- Panel thông tin tòa nhà (Phân tách Vai trò: User vs Staff/Admin) ---------- */
const initBuildingActionPanel = (function() {
  const roomSelect = document.getElementById('panel-room-select');
  const bNameEl    = document.getElementById('panel-building-name');
  const bDescEl    = document.getElementById('panel-building-desc');
  const floorsEl   = document.getElementById('panel-floors');
  const roomCntEl  = document.getElementById('panel-room-count');
  const issueCntEl = document.getElementById('panel-issue-count');
  const optionBtns = document.querySelectorAll('.support-option-btn');
  const staffListEl = document.getElementById('panel-staff-list');
  const staffTabsEl = document.getElementById('staff-filter-tabs');
  let currentMaToaNha = '';
  let cachedStaffItems = [];
  let activeStaffKind = 'all';

  if (roomSelect && optionBtns.length) {
    optionBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        const target  = btn.getAttribute('data-target');
        const maPhong = roomSelect.value;
        const routeMap = {
          suco:   'index.php?c=suco&a=gui',
          timdo:  'index.php?c=tindang&a=dang',
          camera: 'index.php?c=yeucaucamera&a=gui'
        };
        let dest = routeMap[target] || 'index.php';
        if (currentMaToaNha) dest += `&toanha=${encodeURIComponent(currentMaToaNha)}`;
        if (maPhong)         dest += `&phong=${encodeURIComponent(maPhong)}`;

        if (!window.CAMPUS_AUTH) {
          window.location.href = 'index.php?c=auth&a=login&redirect=' + encodeURIComponent(dest);
        } else {
          window.location.href = dest;
        }
      });
    });
  }

  function renderStaffItems() {
    if (!staffListEl) return;
    const list = activeStaffKind === 'all'
      ? cachedStaffItems
      : cachedStaffItems.filter(it => it.kind === activeStaffKind);

    if (!list.length) {
      staffListEl.innerHTML = '<div class="staff-empty-box">Không có phiếu phản ánh / yêu cầu nào thuộc phạm vi của bạn tại tòa nhà này.</div>';
      return;
    }

    staffListEl.innerHTML = list.map(it => {
      const ico = it.kind === 'suco' ? '🔧' : (it.kind === 'camera' ? '📹' : '🎒');
      return `
        <a href="${escapeHtml(it.url)}" class="staff-item-card">
          <div class="staff-item-top">
            <span class="staff-kind-tag kind-${escapeHtml(it.kind)}">${ico} ${escapeHtml(it.kind_label)} #${it.id}</span>
            <span class="badge ${escapeHtml(it.status_cls)}">${escapeHtml(it.status_label)}</span>
          </div>
          <div class="staff-item-title">${escapeHtml(it.title)}</div>
          <div class="staff-item-meta">
            <span>📍 Phòng ${escapeHtml(it.room)}</span>
            <span>👤 ${escapeHtml(it.sender)}</span>
            <span>🕒 ${escapeHtml(it.created_at)}</span>
          </div>
        </a>
      `;
    }).join('');
  }

  if (staffTabsEl) {
    staffTabsEl.querySelectorAll('.staff-tab').forEach(tab => {
      tab.addEventListener('click', () => {
        activeStaffKind = tab.dataset.kind || 'all';
        staffTabsEl.querySelectorAll('.staff-tab').forEach(t => t.classList.toggle('active', t === tab));
        renderStaffItems();
      });
    });
  }

  async function loadStaffItemsForZone(zoneKey) {
    if (!staffListEl) return;
    staffListEl.innerHTML = '<div class="staff-empty-box">Đang tải danh sách phiếu tại tòa nhà...</div>';
    cachedStaffItems = [];
    activeStaffKind = 'all';
    if (staffTabsEl) {
      staffTabsEl.querySelectorAll('.staff-tab').forEach(t => t.classList.toggle('active', t.dataset.kind === 'all'));
    }
    try {
      const res = await fetch(`${API_LIST}?zone=${encodeURIComponent(zoneKey)}`);
      const data = await res.json();
      if (data.success && Array.isArray(data.items)) {
        cachedStaffItems = data.items;
        if (issueCntEl) issueCntEl.textContent = String(cachedStaffItems.length);
      }
    } catch (_) {
      cachedStaffItems = [];
    }
    renderStaffItems();
  }

  return function populateBuildingPanel(zone) {
    const cleanName = String(zone.name || '').replace(/\n/g, ' ');
    const locations = window.CAMPUS_LOCATIONS || { toaNhas: [], phongs: [] };
    const matched = locations.toaNhas.find(
      t => t.KyHieu.toLowerCase() === String(zone.id || '').toLowerCase() ||
           t.TenToaNha.toLowerCase().includes(cleanName.toLowerCase())
    );

    currentMaToaNha = matched ? String(matched.MaToaNha) : '';
    if (bNameEl) bNameEl.textContent = matched ? `${matched.TenToaNha} (${matched.KyHieu})` : cleanName;
    if (bDescEl) bDescEl.textContent = matched?.MoTa || 'Khu vực thuộc khuôn viên Trường Đại học Quy Nhơn.';
    if (floorsEl) floorsEl.textContent = (matched?.SoTang || zone.floors || 1) + ' tầng';
    if (issueCntEl) issueCntEl.textContent = String(reportsByZone[zone.id] || 0);

    const rooms = matched
      ? locations.phongs.filter(p => String(p.MaToaNha) === currentMaToaNha)
      : [];
    if (roomCntEl) roomCntEl.textContent = String(rooms.length);

    if (roomSelect) {
      roomSelect.innerHTML = '<option value="">-- Chọn phòng học (tùy chọn) --</option>';
      rooms.forEach(r => {
        const opt = document.createElement('option');
        opt.value = r.MaPhong;
        opt.textContent = `Tầng ${r.Tang} - Phòng ${r.TenPhong}`;
        roomSelect.appendChild(opt);
      });
    }

    if (window.CAMPUS_IS_STAFF) {
      const zoneKey = matched?.KyHieu || zone.id;
      loadStaffItemsForZone(zoneKey);
    }
  };
})();

function openZone(id) {
  const z = ZONES.find(x => x.id === id);
  if (!z) return;
  currentZone = z;
  const t = ZONE_TYPES[z.type];

  if (mapController) mapController.setActiveZone(id);
  $('#panel-title').textContent = z.name.replace(/\n/g, ' ');
  const chip = $('#panel-type');
  chip.textContent = t.label;
  chip.style.background = t.fill;
  chip.style.borderColor = t.stroke;
  chip.style.color = t.stroke;

  initBuildingActionPanel(z);

  panel.classList.add('open');
  panel.setAttribute('aria-hidden', 'false');
  overlay.hidden = false;
}

function closePanel() {
  panel.classList.remove('open');
  panel.setAttribute('aria-hidden', 'true');
  overlay.hidden = true;
  if (mapController) mapController.setActiveZone(null);
  currentZone = null;
}

/* ---------- Tải số liệu sự cố theo từng khu vực ---------- */
async function loadCounts() {
  try {
    const res = await fetch(API_LIST);
    const data = await res.json();
    if (data.success) reportsByZone = data.counts || {};
  } catch (_) {
    reportsByZone = {};
    localReports.forEach(r => (reportsByZone[r.zone_id] = (reportsByZone[r.zone_id] || 0) + 1));
  }
  updateBadges();
}

/* ---------- Tìm kiếm khu vực ---------- */
function setupSearch() {
  const dl = $('#zone-list');
  const searchInput = $('#zone-search');
  if (!dl || !searchInput) return;

  ZONES.forEach(z => {
    const o = document.createElement('option');
    o.value = z.name.replace(/\n/g, ' ');
    dl.appendChild(o);
  });

  searchInput.addEventListener('change', e => {
    const v = e.target.value.trim().toLowerCase();
    const z = ZONES.find(z => z.name.replace(/\n/g, ' ').toLowerCase() === v)
           || ZONES.find(z => z.name.replace(/\n/g, ' ').toLowerCase().includes(v));
    if (z) {
      if (mapController) mapController.focusZone(z.id);
      openZone(z.id);
      e.target.value = '';
    }
  });
}

/* ---------- Khởi tạo ---------- */
if ($('#panel-close')) $('#panel-close').onclick = closePanel;
if (overlay) overlay.onclick = closePanel;

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') closePanel();
  if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) return;
  if (e.key === '+' || e.key === '=') mapController?.zoomIn();
  if (e.key === '-' || e.key === '_') mapController?.zoomOut();
  if (e.key === '0') mapController?.resetView();
});

mapController = (window.THREE && THREE.OrbitControls) ? initThreeMap() : initFallbackIsoMap();

if ($('#btn-zoom-in'))       $('#btn-zoom-in').onclick       = () => mapController.zoomIn();
if ($('#btn-zoom-out'))      $('#btn-zoom-out').onclick      = () => mapController.zoomOut();
if ($('#btn-reset-view'))    $('#btn-reset-view').onclick    = () => mapController.resetView();
if ($('#btn-compass'))       $('#btn-compass').onclick       = () => mapController.resetView();
if ($('#btn-view-mode'))     $('#btn-view-mode').onclick     = () => mapController.toggleViewMode();
if ($('#btn-auto-rotate'))   $('#btn-auto-rotate').onclick   = () => mapController.toggleAutoRotate();
if ($('#btn-toggle-labels')) $('#btn-toggle-labels').onclick = () => mapController.toggleLabels();

document.querySelectorAll('[data-preset]').forEach(btn => {
  btn.addEventListener('click', () => mapController?.goToPreset(btn.dataset.preset));
});

document.querySelectorAll('[data-theme]').forEach(btn => {
  btn.addEventListener('click', () => mapController?.setTheme(btn.dataset.theme));
});

renderLegend();
setupSearch();
loadCounts();