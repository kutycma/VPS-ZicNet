<?php
// Tự động load Laravel để lấy cấu hình CSDL
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\VpsProvider;

$provider = VpsProvider::where('slug', 'h2cloud')->first();
if (!$provider) {
    die("Không tìm thấy cấu hình H2Cloud trong CSDL.");
}

$urlGetToken   = rtrim($provider->api_endpoint, '/') . '/api/agency/get-token';
$urlGetInfo    = rtrim($provider->api_endpoint, '/') . '/api/agency/get-info';
$urlGetProduct = rtrim($provider->api_endpoint, '/') . '/api/agency/get-product';
$urlListOs     = rtrim($provider->api_endpoint, '/') . '/api/agency/get-list-os';

// Hàm hỗ trợ gọi cURL thuần để bạn dễ dàng nhìn thấy từng cấu trúc
function sendCurl($url, $method, $headers, $body = null) {
    $ch = curl_init();
    $curlHeaders = [];
    foreach ($headers as $k => $v) {
        $curlHeaders[] = "$k: $v";
    }

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($body) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
    }

    // Get response headers as well
    curl_setopt($ch, CURLOPT_HEADER, true);

    $rawResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    
    $resHeaders = substr($rawResponse, 0, $headerSize);
    $resBody = substr($rawResponse, $headerSize);
    
    $error = curl_error($ch);
    curl_close($ch);

    return [
        'http_code' => $httpCode,
        'headers' => $resHeaders,
        'body' => $resBody,
        'error' => $error,
        'request_body' => $body ? json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : 'Không có'
    ];
}

// 1. CHUẨN BỊ HEADER THEO TÀI LIỆU
$baseHeaders = [
    'content-Type' => 'application/json',
    'api-username' => trim($provider->api_username),
    'api-app'      => trim($provider->api_app),
    'api-secret'   => trim($provider->api_secret),
];

// THỰC THI 1: LẤY TOKEN
$resToken = sendCurl($urlGetToken, 'POST', $baseHeaders, [
    'api-username' => trim($provider->api_username),
    'api-app'      => trim($provider->api_app),
    'api-secret'   => trim($provider->api_secret),
]);

$token = null;
$bodyArr = json_decode($resToken['body'], true);
if (is_array($bodyArr) && isset($bodyArr['auth-token'])) {
    $token = $bodyArr['auth-token'];
}

// Nếu có Token, múc luôn các API tiếp theo
$resInfo = null;
$resProduct = null;
$resOs = null;

if ($token) {
    // Thêm token vào Headers cho các Yêu cầu phía sau
    $authHeaders = array_merge($baseHeaders, [
        'auth-token' => $token
    ]);
    
    // GỬI GET RỖNG BODY CHO CÁC MODULE TIẾP THEO
    $resInfo = sendCurl($urlGetInfo, 'GET', $authHeaders);
    $resProduct = sendCurl($urlGetProduct, 'GET', $authHeaders);
    $resOs = sendCurl($urlListOs, 'GET', $authHeaders);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>H2Cloud Raw API Tester</title>
    <style>
        body { font-family: "Courier New", Courier, monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; font-size: 14px;}
        h2 { color: #ffeb3b; text-align: center; border-bottom: 1px dashed #555; padding-bottom: 10px; }
        .box { background: #2d2d2d; border: 1px solid #444; margin-bottom: 30px; padding: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.5); }
        h3 { color: #4fc3f7; margin-top: 0; font-size: 1.2rem; }
        .success { color: #4CAF50; font-weight: bold; }
        .error { color: #f44336; font-weight: bold; background: rgba(244,67,54,0.1); padding: 2px 6px; border-radius: 4px; }
        pre { background: #000; padding: 15px; border-radius: 5px; white-space: pre-wrap; word-wrap: break-word; color: #ce9178; border-left: 4px solid #569cd6; }
        .highlight { color: #9cdcfe; font-weight: bold; }
    </style>
</head>
<body>
    <h2>🚀 BỘ KIỂM THỬ TRỰC TIẾP API H2CLOUD (CURL NATIVE)</h2>
    <p>Script PHP này nằm tách biệt hoàn toàn so với Code chính của VPS ZicNet. Nó sử dụng cURL gọi thẳng tới Server H2Cloud và phơi bày toàn bộ dữ liệu phản hồi thô (Raw) để bạn có thể gửi cho kỹ thuật H2Cloud phân tích lỗi quyền (2).</p>

    <!-- TEST 1 -->
    <div class="box">
        <h3>1. LẤY TOKEN MỚI (POST <?php echo $urlGetToken; ?>)</h3>
        <p><strong>A. Tham số Header Truyền đi:</strong> Cấu hình từ CSDL (đã ẩn chi tiết Secret)</p>
        <p><strong>B. Tham số JSON Body Truyền đi (Như tài liệu yêu cầu):</strong></p>
        <pre><?php // Ẩn secret
        $hideData = [
            'api-username' => $provider->api_username,
            'api-app' => substr($provider->api_app, 0, 8).'*******',
            'api-secret' => substr($provider->api_secret, 0, 5).'************************'
        ];
        echo json_encode($hideData, JSON_PRETTY_PRINT);
        ?></pre>
        
        <p><strong>C. Phản hồi thực tế (HTTP <?php echo $resToken['http_code']; ?>):</strong></p>
        <pre><?php echo htmlspecialchars($resToken['body']); ?></pre>
    </div>

    <!-- TEST 2 -->
    <?php if ($token): ?>
    <div class="box" style="<?php echo (strpos($resInfo['body'], '"error":0') === false) ? 'border-color: #f44336;' : ''; ?>">
        <h3>2. LẤY CHI TIẾT ĐẠI LÝ (GET <?php echo $urlGetInfo; ?>)</h3>
        <p><strong>A. Headers Đã Nối (Quan trọng nhất):</strong> Hệ thống đã mang "auth-token" vừa đào được ở bước 1 gắn lên Header gửi đi đợt này thành công.</p>
        
        <p><strong>B. Response Của H2Cloud Trả Về Dành Cho Tài Khoản Của Bạn:</strong> <?php echo (strpos($resInfo['body'], '"error":1') !== false) ? "<span class='error'>BỊ TỪ CHỐI</span>" : "<span class='success'>TRUY CẬP THÀNH CÔNG</span>"; ?></p>
        <pre><?php echo htmlspecialchars($resInfo['body']); ?></pre>
        <?php if (strpos($resInfo['body'], '(2)') !== false): ?>
             <p style="color: #ff9800;">👉 <strong>Phân tích:</strong> Lỗi (2) đã bị bắt quả tang! API Key của bạn lấy được Token thành công, nhưng quyền của Token đó đã bị khoá tính năng GET INFO, hoặc là IP máy chủ này đang bị chặn lửa firewall bên họ. Chụp ngay cái khung số 2 này gửi kỹ thuật cho họ nhé!</p>
        <?php endif; ?>
    </div>

    <div class="box">
        <h3>3. LẤY TEST DANH SÁCH SẢN PHẨM KHÁC LỖI NÀO (GET <?php echo $urlGetProduct; ?>)</h3>
        <p>Test xem ngoài Get-Info bị chặn, thì hàm Get-Product có rỗng hay cũng báo lỗi (2):</p>
        <pre><?php echo htmlspecialchars($resProduct['body']); ?></pre>
    </div>

    <div class="box">
        <h3>4. LẤY HỆ ĐIỀU HÀNH (GET <?php echo $urlListOs; ?>)</h3>
        <pre><?php echo htmlspecialchars($resOs['body']); ?></pre>
    </div>

    <?php
    $urlListVpsVN = rtrim($provider->api_endpoint, '/') . '/api/agency/vps/get-list-vps?type=all&qtt=30&page=0';
    $urlListVpsNN = rtrim($provider->api_endpoint, '/') . '/api/agency/vps/get-list-vps-nn?type=all&qtt=30&page=0';
    
    $resVpsVN = sendCurl($urlListVpsVN, 'GET', $authHeaders);
    $resVpsNN = sendCurl($urlListVpsNN, 'GET', $authHeaders);
    ?>

    <div class="box">
        <h3>5. DANH SÁCH VPS VN (GET <?php echo $urlListVpsVN; ?>)</h3>
        <p>Phản hồi từ cổng get-list-vps:</p>
        <pre><?php echo htmlspecialchars($resVpsVN['body']); ?></pre>
    </div>

    <div class="box">
        <h3>6. DANH SÁCH VPS NN (GET <?php echo $urlListVpsNN; ?>)</h3>
        <p>Phản hồi từ cổng get-list-vps-nn:</p>
        <pre><?php echo htmlspecialchars($resVpsNN['body']); ?></pre>
    </div>
    
    <?php else: ?>
    <div class="box">
        <h3 class="error">⛔ KHÔNG THỂ TIẾP TỤC CÁC BƯỚC SAU</h3>
        <p>Vì token ở Bước 1 lấy thất bại nên hệ thống không dám dội bom gọi tiếp.</p>
    </div>
    <?php endif; ?>

</body>
</html>
