/* =====================================================================
 * DỮ LIỆU SƠ ĐỒ KHUÔN VIÊN 3D – QNU CAMPUSFIX 2.0
 * Toạ độ mặt bằng chuẩn (2576 x 1426).
 * Tích hợp bảng màu kiến trúc hiện đại, cảnh quan sân quảng trường,
 * vạch qua đường, đèn chiếu sáng ban đêm và công trình điểm nhấn.
 * ===================================================================== */

const CAMPUS_SIZE = { width: 2576, depth: 1426 };

const ZONE_TYPES = {
  theory: {
    label: 'Các toà nhà lý thuyết',
    fill: '#dbeafe',
    stroke: '#2563eb',
    top: '#eef4ff',
    wall: '#f1f5f9',
    accent: '#2563eb',
    window: '#1e3a8a',
    roof: '#cbd5e1'
  },
  practice: {
    label: 'Các toà nhà thực hành',
    fill: '#dcfce7',
    stroke: '#16a34a',
    top: '#ecfdf5',
    wall: '#eff6f2',
    accent: '#16a34a',
    window: '#14532d',
    roof: '#bbf7d0'
  },
  parking: {
    label: 'Các khu vực trại xe',
    fill: '#ffedd5',
    stroke: '#ea580c',
    top: '#fed7aa',
    wall: '#fdba74',
    accent: '#ea580c',
    window: '#9a3412',
    roof: '#f97316'
  },
  dorm: {
    label: 'Khu kí túc xá sinh viên',
    fill: '#fef3c7',
    stroke: '#d97706',
    top: '#fffbeb',
    wall: '#fef9eb',
    accent: '#d97706',
    window: '#78350f',
    roof: '#fde68a'
  },
  other: {
    label: 'Các công trình khác',
    fill: '#f3e8ff',
    stroke: '#7c3aed',
    top: '#f5f3ff',
    wall: '#f4f3fa',
    accent: '#7c3aed',
    window: '#3b0764',
    roof: '#ddd6fe'
  }
};

/* Danh mục phản ánh theo loại khu vực */
const CATEGORIES = {
  theory: [
    { id: 'equipment', icon: '📽️', label: 'Thiết bị phòng học' },
    { id: 'electric',  icon: '💡', label: 'Điện / Đèn / Quạt / Máy lạnh' },
    { id: 'water',     icon: '🚰', label: 'Nước / Vệ sinh' },
    { id: 'facility',  icon: '🪑', label: 'Bàn ghế / Cơ sở vật chất' },
    { id: 'wifi',      icon: '📶', label: 'Wifi / Mạng' },
    { id: 'security',  icon: '🛡️', label: 'An ninh / Trật tự' }
  ],
  practice: [
    { id: 'lab',       icon: '🔬', label: 'Thiết bị thực hành / Thí nghiệm' },
    { id: 'safety',    icon: '🧯', label: 'An toàn PCCC / Hoá chất' },
    { id: 'electric',  icon: '💡', label: 'Điện / Đèn / Quạt / Máy lạnh' },
    { id: 'water',     icon: '🚰', label: 'Nước / Vệ sinh' },
    { id: 'wifi',      icon: '📶', label: 'Wifi / Mạng' },
    { id: 'security',  icon: '🛡️', label: 'An ninh / Trật tự' }
  ],
  parking: [
    { id: 'lost',      icon: '🔑', label: 'Mất / Thất lạc tài sản' },
    { id: 'roof',      icon: '🏚️', label: 'Mái che / Nền sân hư hỏng' },
    { id: 'camera',    icon: '📹', label: 'Camera / Chiếu sáng' },
    { id: 'order',     icon: '🛵', label: 'Sắp xếp xe / Trật tự' },
    { id: 'security',  icon: '🛡️', label: 'An ninh' },
    { id: 'other',     icon: '📝', label: 'Khác' }
  ],
  dorm: [
    { id: 'room',      icon: '🛏️', label: 'Phòng ở / Giường tủ' },
    { id: 'electric',  icon: '💡', label: 'Điện' },
    { id: 'water',     icon: '🚿', label: 'Nước / Nhà vệ sinh' },
    { id: 'wifi',      icon: '📶', label: 'Wifi / Mạng' },
    { id: 'security',  icon: '🛡️', label: 'An ninh / Trật tự' },
    { id: 'health',    icon: '🩺', label: 'Y tế / Vệ sinh môi trường' }
  ],
  other: [
    { id: 'facility',  icon: '🏗️', label: 'Cơ sở vật chất' },
    { id: 'electric',  icon: '💡', label: 'Điện / Chiếu sáng' },
    { id: 'water',     icon: '🚰', label: 'Nước / Vệ sinh' },
    { id: 'service',   icon: '🍜', label: 'Dịch vụ / Chất lượng phục vụ' },
    { id: 'security',  icon: '🛡️', label: 'An ninh / Trật tự' },
    { id: 'other',     icon: '📝', label: 'Khác' }
  ]
};

/* Các khu vực & thông số khối 3D (khớp 100% KyHieu trong CSDL) */
const ZONES = [
  { id: 'A8',      name: 'KHU A8',                         type: 'theory',   floors: 4,  arch: 'academic', rect: [1069,   0, 154, 312] },
  { id: 'TC',      name: 'TÒA NHÀ THỂ CHẤT',               type: 'practice', floors: 3,  arch: 'sports',   rect: [1304,  84, 330, 228] },
  { id: 'A3',      name: 'KHU A3',                         type: 'theory',   floors: 4,  arch: 'academic', rect: [ 657, 225, 153, 408] },
  { id: 'TX-A3',   name: 'TRẠI XE\nA3',                    type: 'parking',  floors: 1,  arch: 'parking',  rect: [ 810, 225, 125, 408] },
  { id: 'TX-A3GV', name: 'TRẠI XE A3\n(GIẢNG VIÊN)',       type: 'parking',  floors: 1,  arch: 'parking',  rect: [ 935, 356, 187, 237] },
  { id: 'A5',      name: 'KHU A5',                         type: 'practice', floors: 3,  arch: 'lab',      rect: [ 347, 413, 260, 136] },
  { id: 'A6',      name: 'KHU THỰC HÀNH A6',               type: 'practice', floors: 3,  arch: 'lab',      rect: [   6, 437, 342, 194] },
  { id: 'A7',      name: 'KHU THỰC HÀNH\nA7',              type: 'practice', floors: 3,  arch: 'lab',      rect: [ 934, 592, 244, 160] },
  { id: '15T',     name: 'Tòa nhà 15T',                    type: 'other',    floors: 15, arch: 'tower',    rect: [1178, 683, 212, 188] },
  { id: 'KTX-YT',  name: 'KÝ TÚC\nXÁ VÀ\nNHÀ Y TẾ',        type: 'dorm',     floors: 5,  arch: 'dorm',     rect: [1465, 480, 254, 323] },
  { id: 'CANTIN',  name: 'KHU CĂN TIN',                    type: 'other',    floors: 2,  arch: 'canteen',  rect: [1800, 636, 224, 167] },
  { id: 'KTX',     name: 'KÝ TÚC\nXÁ',                     type: 'dorm',     floors: 5,  arch: 'dorm',     rect: [2085, 458, 245, 352] },
  { id: 'A1',      name: 'KHU A1',                         type: 'theory',   floors: 4,  arch: 'academic', rect: [   2, 683, 152, 573] },
  { id: 'TX-A1',   name: 'KHU\nVỰC\nDỰNG\nXE\nA1',         type: 'parking',  floors: 1,  arch: 'parking',  rect: [ 154, 683,  67, 573] },
  { id: 'TX-A2a',  name: 'KHU\nVỰC\nDỰNG\nXE\nA2',         type: 'parking',  floors: 1,  arch: 'parking',  rect: [ 273, 683,  67, 571] },
  { id: 'A2',      name: 'KHU A2',                         type: 'theory',   floors: 4,  arch: 'academic', rect: [ 340, 683, 153, 573] },
  { id: 'TX-A2b',  name: 'KHU VỰC\nDỰNG XE\nA2',           type: 'parking',  floors: 1,  arch: 'parking',  rect: [ 493, 683, 165, 189] },
  { id: 'HT-A',    name: 'HỘI TRƯỜNG\nA',                  type: 'other',    floors: 2,  arch: 'hall',     rect: [ 658, 685, 190, 187] },
  { id: 'TX-A2c',  name: 'KHU VỰC DỰNG XE\nA2',            type: 'parking',  floors: 1,  arch: 'parking',  rect: [ 569, 932, 312, 408] },
  { id: 'TH-NVLN', name: 'Khu thực hành\nNV LN & KS',      type: 'practice', floors: 3,  arch: 'lab',      rect: [ 881,1038, 212, 178] },
  { id: '4T',      name: 'Tòa nhà 4T',                     type: 'practice', floors: 4,  arch: 'lab',
    poly: '1499,946 1612,1108 2239,1108 2239,1326 1513,1326 1328,1064', label: [1876, 1217] },
  { id: 'HT-B',    name: 'HỘI TRƯỜNG\nB',                  type: 'other',    floors: 2,  arch: 'hall',     rect: [2048, 919, 191, 189] },
  { id: 'THUVIEN', name: 'KHU THƯ VIỆN\nTRƯỜNG',           type: 'other',    floors: 4,  arch: 'library',  rect: [2305, 872, 271, 291] }
];

const GATES = [
  { name: 'Cổng giảng đường',      rect: [ 435, 1381, 233, 38] },
  { name: 'Cổng chính - Cổng QNU', rect: [1108, 1381, 351, 44] }
];

/* Tuyến đường nội bộ khuôn viên [x, y, w, h] */
const CAMPUS_ROADS = [
  [0, 646, 2576, 32],       // Trục ngang trung tâm
  [0, 1344, 2576, 38],      // Trục dọc mặt tiền cổng
  [525, 895, 2050, 28],     // Trục ngang phía nam
  [1245, 320, 36, 1055],    // Trục chính từ Cổng QNU lên 15T / A8
  [525, 650, 34, 725],      // Trục từ Cổng giảng đường
  [1745, 450, 28, 460],     // Lối đi khu KTX - Căn tin
  [2260, 450, 28, 720]      // Lối đi khu Thư viện - KTX
];

/* Quảng trường & sân lát gạch [x, y, w, h, color] */
const CAMPUS_PLAZAS = [
  [1140, 880, 285, 160, '#e2e8f0'],   // Quảng trường trung tâm trước Tòa 15T
  [650, 880, 210, 45, '#e2e8f0'],     // Sân trước Hội trường A
  [1775, 460, 290, 165, '#e6eef5'],   // Sân sinh hoạt giữa KTX & Căn tin
  [2290, 1170, 275, 150, '#e2e8f0']   // Sân đọc sách phía nam Thư viện
];

/* Sân thể thao ngoài trời trang trí (khu vực trống phía bắc cạnh Tòa nhà Thể chất) */
const CAMPUS_COURTS = [
  { x: 1700, y: 80, w: 280, h: 160, color: '#2b6cb0', border: '#ffffff' },
  { x: 2020, y: 80, w: 280, h: 160, color: '#c05621', border: '#ffffff' }
];

/* Vạch qua đường tại các giao lộ [x, y, horizontal] */
const CAMPUS_CROSSWALKS = [
  [1263, 1326, true],
  [1263, 935, true],
  [1263, 628, true],
  [542, 1326, true],
  [542, 628, true],
  [1215, 909, false],
  [1310, 909, false]
];

/* Đèn đường chiếu sáng ban đêm dọc các trục chính [x, y] */
const CAMPUS_LAMPS = [
  [1222, 1270], [1304, 1270], [1222, 1110], [1304, 1110],
  [1222, 960],  [1304, 960],  [1222, 540],  [1304, 540],
  [1222, 390],  [1304, 390],
  [504, 1260],  [504, 1080],  [504, 930],   [504, 760],
  [200, 632],   [750, 632],   [1520, 632],  [1980, 632],  [2380, 632],
  [980, 882],   [1620, 882],  [1960, 882],  [2380, 882],
  [350, 1332],  [820, 1332],  [1580, 1332], [1980, 1332], [2380, 1332]
];

/* Vị trí cây xanh trang trí khuôn viên (đã kiểm tra không trùng tòa nhà) */
const CAMPUS_TREES = [
  [120, 120], [260, 120], [420, 120], [560, 120], [760, 110], [920, 110],
  [2380, 140], [2480, 140], [2450, 260],
  [1740, 320], [1950, 320], [2140, 320], [2450, 340],
  [120, 340], [280, 340], [480, 310],
  [1380, 420], [1380, 560],
  [1785, 480], [1980, 480],
  [2440, 550], [2440, 720],
  [960, 855], [1080, 855], [1680, 860], [1880, 860],
  [1175, 1120], [1175, 1255],
  [120, 1310], [300, 1310], [460, 1310], [960, 1310],
  [1650, 1320], [1900, 1320], [2150, 1320], [2480, 1240], [2480, 1315]
];
