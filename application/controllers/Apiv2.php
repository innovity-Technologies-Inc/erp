<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'third_party/JWT/JWT.php');
require_once(APPPATH . 'third_party/JWT/Key.php');

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Apiv2 extends CI_Controller {

    private $jwt_key;
    private $jwt_algo;
    private $jwt_ttl;
    private $refresh_ttl;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Api_model');
        $this->load->helper(['security']);
        $this->load->library('form_validation');
        $this->load->library('session');
        $this->config->load('jwt');
        $this->load->library('ciqrcode');

        $this->jwt_key = $this->config->item('jwt_secret_key');
        $this->jwt_algo = $this->config->item('jwt_algorithm');
        $this->jwt_ttl = $this->config->item('jwt_token_ttl');
        $this->refresh_ttl = $this->config->item('jwt_refresh_ttl');
    }

    /**
         * @OA\Post(
         *     path="/apiv2/create_user",
         *     tags={"API Users"},
         *     summary="Create a new API user",
         *     description="Creates a new API user with a username, password, usertype and creator ID.",
         *     @OA\RequestBody(
         *         required=true,
         *         @OA\JsonContent(
         *             required={"username","password","usertype","createby"},
         *             @OA\Property(property="username", type="string", example="apiuser1"),
         *             @OA\Property(property="password", type="string", example="secure123"),
         *             @OA\Property(property="usertype", type="string", example="admin"),
         *             @OA\Property(property="createby", type="integer", example=1)
         *         )
         *     ),
         *     @OA\Response(
         *         response=200,
         *         description="API user created successfully",
         *         @OA\JsonContent(
         *             @OA\Property(property="status", type="string", example="success"),
         *             @OA\Property(property="message", type="string", example="API user created successfully")
         *         )
         *     ),
         *     @OA\Response(response=400, description="Validation failed"),
         *     @OA\Response(response=500, description="Insert failed")
         * )
     */

    public function create_user()
    {
        $input = json_decode(trim(file_get_contents("php://input")), true);
        $_POST = $input;

        $this->form_validation->set_rules('username', 'Username', 'required|is_unique[api_users.username]');
        $this->form_validation->set_rules('password', 'Password', 'required|min_length[6]');
        $this->form_validation->set_rules('usertype', 'User Type', 'required');
        $this->form_validation->set_rules('createby', 'Created By', 'required|integer');

        if ($this->form_validation->run() === FALSE) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode([
                    'status' => 'error',
                    'errors' => $this->form_validation->error_array()
                ]));
        }

        $data = [
            'username' => $input['username'],
            'password' => password_hash($input['password'], PASSWORD_BCRYPT),
            'usertype' => $input['usertype'],
            'createby' => $input['createby']
        ];

        $inserted = $this->db->insert('api_users', $data);

        if ($inserted) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => 'success',
                    'message' => 'API user created successfully'
                ]));
        } else {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(500)
                ->set_output(json_encode([
                    'status' => 'error',
                    'message' => 'Failed to create API user'
                ]));
        }
    }

    /**
     * @OA\Post(
     *     path="/apiv2/verify_user_credentials",
     *     tags={"API Users"},
     *     summary="Verify username and password",
     *     description="Checks if the provided username and password match a registered API user",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"username","password"},
     *             @OA\Property(property="username", type="string", example="apiuser1"),
     *             @OA\Property(property="password", type="string", example="secure123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Credentials matched",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Invalid credentials")
     * )
     */
    public function verify_user_credentials()
    {
        $input = json_decode(trim(file_get_contents("php://input")), true);

        if (!isset($input['username']) || !isset($input['password'])) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode([
                    'status' => 'error',
                    'message' => 'Username and password required'
                ]));
        }

        $user = $this->Api_model->verify_api_user_credentials($input['username'], $input['password']);

        if ($user) {
            unset($user['password']);
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => 'success',
                    'user' => $user
                ]));
        } else {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(401)
                ->set_output(json_encode([
                    'status' => 'error',
                    'message' => 'Invalid username or password'
                ]));
        }
    }

    // JWT login, refresh and protection logic will be added here next

    /**
     * @OA\Post(
     *     path="/apiv2/login",
     *     tags={"Authentication"},
     *     summary="Login with username and password",
     *     description="Generates an access token and refresh token for valid credentials.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"username", "password"},
     *             @OA\Property(property="username", type="string", example="apiuser1"),
     *             @OA\Property(property="password", type="string", example="secure123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="refresh_token", type="string"),
     *             @OA\Property(property="expires_in", type="integer", example=3600)
     *         )
     *     ),
     *     @OA\Response(response=400, description="Username and password required"),
     *     @OA\Response(response=401, description="Invalid credentials")
     * )
     */

    public function login()
    {
        // Enforce POST method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->_bad_request('Only POST method is allowed');
        }

        // Decode JSON input
        $input = json_decode(trim(file_get_contents("php://input")), true);

        // Check required fields
        if (empty($input['username']) || empty($input['password'])) {
            return $this->_bad_request('Username and password are required');
        }

        // Authenticate user
        $user = $this->Api_model->verify_api_user_credentials($input['username'], $input['password']);

        if (!$user) {
            return $this->_unauthorized('Invalid username or password');
        }

        // Generate tokens
        $access_token = JWT::encode([
            'iat' => time(),
            'exp' => time() + $this->jwt_ttl,
            'uid' => $user['id'],
            'username' => $user['username'],
            'usertype' => $user['usertype']
        ], $this->jwt_key, $this->jwt_algo);

        $refresh_token = JWT::encode([
            'iat' => time(),
            'exp' => time() + $this->refresh_ttl,
            'uid' => $user['id']
        ], $this->jwt_key, $this->jwt_algo);

        // Return success response
        return $this->_success([
            'access_token'  => $access_token,
            'refresh_token' => $refresh_token,
            'expires_in'    => $this->jwt_ttl
        ], 'Login successful');
    }


    public function refresh_token()
    {
        // Enforce POST method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->_bad_request('Only POST method is allowed');
        }

        // Decode input
        $input = json_decode(trim(file_get_contents("php://input")), true);
        $refresh_token = $input['refresh_token'] ?? null;

        // Check if token exists
        if (empty($refresh_token)) {
            return $this->_bad_request('Refresh token is required');
        }

        try {
            // Decode token
            $decoded = JWT::decode($refresh_token, new \Firebase\JWT\Key($this->jwt_key, $this->jwt_algo));

            // Retrieve user
            $user = $this->Api_model->get_api_user_by_id($decoded->uid);

            if (!$user) {
                return $this->_unauthorized('User not found');
            }

            // Generate new access token
            $new_access_token = JWT::encode([
                'iat' => time(),
                'exp' => time() + $this->jwt_ttl,
                'uid' => $user['id'],
                'username' => $user['username'],
                'usertype' => $user['usertype']
            ], $this->jwt_key, $this->jwt_algo);

            // Send response
            return $this->_success([
                'access_token' => $new_access_token,
                'expires_in'   => $this->jwt_ttl
            ], 'Access token refreshed');

        } catch (\Firebase\JWT\ExpiredException $e) {
            return $this->_unauthorized('Refresh token has expired');
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            return $this->_unauthorized('Invalid token signature');
        } catch (Exception $e) {
            log_message('error', 'Token refresh error: ' . $e->getMessage());
            return $this->_unauthorized('Invalid or malformed refresh token');
        }
    }

    /**
     * @OA\Get(
     *     path="/apiv2/protected_data",
     *     tags={"Protected"},
     *     summary="Access protected data",
     *     description="Requires a valid Bearer access token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Token is valid",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Token is valid"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */ 

    public function protected_data()
    {
        $user = $this->authenticate_token();
        if (!$user) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(401)
                ->set_output(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => 'success',
                'message' => 'Token is valid',
                'user' => $user
            ]));
    }

    private function authenticate_token()
    {
        $auth_header = $this->input->get_request_header('Authorization');
        if (!$auth_header || !preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
            return false;
        }
    
        $token = $matches[1];
    
        try {
            $decoded = JWT::decode($token, new Key($this->jwt_key, $this->jwt_algo));
    
            // Reject token if it has no username/usertype (i.e., it's likely a refresh_token)
            if (!isset($decoded->username) || !isset($decoded->usertype)) {
                return false; // It's a refresh token, not access
            }
    
            return $decoded;
    
        } catch (Exception $e) {
            return false;
        }
    }



    /**
         * @OA\Get(
         *     path="/apiv2/category_list",
         *     tags={"Categories"},
         *     summary="Get all product categories",
         *     description="Returns all product categories. Requires a valid Bearer access token.",
         *     security={{"bearerAuth":{}}},
         *     @OA\Response(
         *         response=200,
         *         description="List of categories",
         *         @OA\JsonContent(
         *             @OA\Property(property="response", type="object",
         *                 @OA\Property(property="status", type="string", example="ok"),
         *                 @OA\Property(property="categories", type="array",
         *                     @OA\Items(type="object")  // You can add specific properties here if you want
         *                 ),
         *                 @OA\Property(property="total_val", type="integer", example=15)
         *             )
         *         )
         *     ),
         *     @OA\Response(
         *         response=401,
         *         description="Unauthorized - Token missing or invalid"
         *     ),
         *     @OA\Response(
         *         response=200,
         *         description="No records found",
         *         @OA\JsonContent(
         *             @OA\Property(property="response", type="object",
         *                 @OA\Property(property="status", type="string", example="error"),
         *                 @OA\Property(property="message", type="string", example="No Record found")
         *             )
         *         )
         *     )
         * )
     */

    public function category_list()
    {
        $user = $this->authenticate_token();
        if (!$user) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(401)
                ->set_output(json_encode([
                    'status' => 'error',
                    'message' => 'Unauthorized. Please provide a valid access token.'
                ]));
        }

        $categories = $this->Api_model->category_list();
        $formatted = [];

        foreach ($categories as $cat) {
            $path_parts = explode('->', $cat['category_name']);
            $total_parts = count($path_parts);

            $formatted[] = [
                'category_id'   => $cat['category_id'],
                'parent_id'     => $cat['parent_id'], // ✅ added here
                'status'        => $cat['status'],
                'parent_name'   => $total_parts > 0 ? $path_parts[0] : null,
                'subcategory'   => $total_parts > 1 ? $path_parts[$total_parts - 2] : null,
                'category_name' => end($path_parts),
                'full_path'     => implode(' -> ', $path_parts),
            ];
        }

        if (!empty($formatted)) {
            $json['response'] = [
                'status'     => 'ok',
                'categories' => $formatted,
                'total_val'  => count($formatted),
            ];
        } else {
            $json['response'] = [
                'status'  => 'error',
                'message' => 'No Record found'
            ];
        }

        echo json_encode($json, JSON_UNESCAPED_UNICODE);
    }

    // From here all application functionality will be there 


    /**
         * @OA\Get(
         *     path="/apiv2/product_list",
         *     tags={"Products"},
         *     summary="Get paginated list of products with stock info and QR codes",
         *     description="Returns a list of products with stock quantity, barcode, and QR code info. Requires a valid Bearer access token.",
         *     security={{"bearerAuth":{}}},
         *     @OA\Parameter(
         *         name="start",
         *         in="query",
         *         required=false,
         *         description="Pagination start index",
         *         @OA\Schema(type="integer", example=1)
         *     ),
         *     @OA\Response(
         *         response=200,
         *         description="Successful response with product list",
         *         @OA\JsonContent(
         *             @OA\Property(property="response", type="object",
         *                 @OA\Property(property="status", type="string", example="ok"),
         *                 @OA\Property(property="product_list", type="array",
         *                     @OA\Items(type="object",
         *                         @OA\Property(property="product_id", type="string"),
         *                         @OA\Property(property="stock_qty", type="number"),
         *                         @OA\Property(property="qr_code", type="string", example="http://yourdomain.com/my-assets/image/qr/1234.png"),
         *                         @OA\Property(property="bar_code", type="string", example="http://yourdomain.com/Cbarcode/barcode_generator/1234"),
         *                         @OA\Property(property="product_info_bybarcode", type="object")
         *                     )
         *                 ),
         *                 @OA\Property(property="total_val", type="integer", example=250)
         *             )
         *         )
         *     ),
         *     @OA\Response(
         *         response=401,
         *         description="Unauthorized - Token missing or invalid"
         *     )
         * )
     */

    public function product_list()
    {
        // Step 1: Authenticate the Access Token (First Layer Login)
        $user = $this->authenticate_token();
        if (!$user) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(401)
                ->set_output(json_encode([
                    'status' => 'error',
                    'message' => 'Unauthorized. Please provide a valid access token.'
                ]));
        }

        // 🔍 Debugging - Log the headers
        log_message('info', 'Headers: ' . json_encode(getallheaders()));

        // Step 2: Authenticate the 2nd Layer (Optional)
        $second_token = $this->input->get_request_header('X-Second-Token');
        $view_sensitive_data = false;

        if ($second_token) {
            try {
                // ✅ Decode with the new syntax for Firebase JWT
                $decoded = JWT::decode($second_token, new Key($this->jwt_key, $this->jwt_algo));
                if (isset($decoded->customer_id)) {
                    log_message('info', 'Second Layer Token Decoded Successfully.');
                    $view_sensitive_data = true;
                }
            } catch (Exception $e) {
                log_message('error', 'Second Layer Token Decode Failed: ' . $e->getMessage());
            }
        } else {
            log_message('error', 'X-Second-Token header not found.');
        }

        // Step 3: Fetch Products based on the start parameter
        $start = $this->input->get('start');
        $products = $start ?
            $this->Api_model->product_list($limit = 15, $start == 1 ? 0 : $start) :
            $this->Api_model->searchproduct_list();

        // Step 4: Fetch all categories and map them by ID
        $category_map = [];
        $category_list = $this->db->get('product_category')->result();
        foreach ($category_list as $cat) {
            $category_map[$cat->category_id] = $cat->category_name;
        }

        // Step 5: Process each product
        if (!empty($products)) {
            foreach ($products as $k => $v) {
                // Calculate stock quantity
                $totalSalesQnty = $this->db->select('SUM(quantity) AS totalSalesQnty')
                    ->where('product_id', $v['product_id'])
                    ->get('invoice_details')
                    ->row()->totalSalesQnty ?? 0;

                $totalBuyQnty = $this->db->select('SUM(quantity) AS totalBuyQnty')
                    ->where('product_id', $v['product_id'])
                    ->get('product_purchase_details')
                    ->row()->totalBuyQnty ?? 0;

                $stokqty = $totalBuyQnty - $totalSalesQnty;

                // ✅ Fix image path
                if (!empty($products[$k]['image'])) {
                    $products[$k]['image'] = base_url(str_replace('./', '', $products[$k]['image']));
                }

                // ✅ Attach category name
                $products[$k]['category_name'] = $category_map[$v['category_id']] ?? null;

                // ✅ Attach stock if 2nd layer is validated
                if ($view_sensitive_data) {
                    $products[$k]['stock_qty'] = $stokqty ?? 0;
                } else {
                    unset($products[$k]['price']);
                    unset($products[$k]['stock_qty']);
                }

                // ✅ QR Code generation
                $qrImagePath = FCPATH . 'my-assets/image/qr/' . $products[$k]['product_id'] . '.png';
                if (!file_exists($qrImagePath)) {
                    $this->ciqrcode->initialize([
                        'cacheable' => true,
                        'cachedir'  => '',
                        'errorlog'  => '',
                        'quality'   => true,
                        'size'      => '1024',
                        'black'     => [224, 255, 255],
                        'white'     => [70, 130, 180]
                    ]);

                    $params = [
                        'data'     => $products[$k]['product_id'],
                        'level'    => 'H',
                        'size'     => 10,
                        'savename' => $qrImagePath
                    ];

                    $this->ciqrcode->generate($params);
                }

                // ✅ Append barcode and QR code URLs
                $product_info = $this->Api_model->product_info_bybarcode($products[$k]['product_id']);
                
                // ✅ Remove price and stock if not 2nd layer logged in
                if (!$view_sensitive_data) {
                    unset($product_info['price']);
                    unset($product_info['stock']);
                }

                $products[$k]['product_info_bybarcode'] = $product_info;
                $products[$k]['qr_code'] = base_url('my-assets/image/qr/' . $products[$k]['product_id'] . '.png');
                $products[$k]['bar_code'] = base_url('Cbarcode/barcode_generator/' . $products[$k]['product_id']);
            }
        }

        // Step 6: Return JSON response
        $json['response'] = !empty($products) ? [
            'status'       => 'ok',
            'product_list' => $products,
            'total_val'    => $this->db->count_all("product_information"),
        ] : [
            'status'       => 'error',
            'product_list' => [],
            'message'      => 'No Product Found',
        ];

        echo json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }


    public function customer_comission_by_email()
    {
        try {
            // ✅ Step 1: First-layer token authentication
            $user = $this->authenticate_token();
            if (!$user) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(401)
                    ->set_output(json_encode([
                        'status'  => 'unauthorized',
                        'message' => 'Unauthorized. Please provide a valid access token.'
                    ]));
            }

            // ✅ Step 2: Log headers
            log_message('info', 'Headers: ' . json_encode(getallheaders()));

            // ✅ Step 3: Optional second-layer token validation
            $second_token = $this->input->get_request_header('X-Second-Token');
            $view_sensitive_data = false;

            if ($second_token) {
                try {
                    $decoded = JWT::decode($second_token, new Key($this->jwt_key, $this->jwt_algo));
                    if (isset($decoded->customer_id)) {
                        log_message('info', 'Second-layer token decoded successfully.');
                        $view_sensitive_data = true;
                    }
                } catch (Exception $e) {
                    log_message('error', 'Second-layer token decode failed: ' . $e->getMessage());
                }
            } else {
                log_message('info', 'Second-layer token not provided.');
            }

            // ✅ Step 4: Validate input
            $email = $this->input->get('email');
            log_message('debug', 'Checking customer commission for email: ' . $email);

            if (empty($email)) {
                return $this->_bad_request('Missing required parameter: email');
            }

            // ✅ Step 5: Fetch customer info
            $customer = $this->Api_model->get_customer_by_email($email);

            if (!$customer || !isset($customer->customer_id)) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(404)
                    ->set_output(json_encode([
                        'status'  => 'not_found',
                        'message' => 'No customer found with this email'
                    ]));
            }

            // ✅ Step 6: Fetch active commission
            $commission = $this->db->select('commision_value, comission_type')
                ->from('customer_comission')
                ->where('customer_id', $customer->customer_id)
                ->where('status', 1)
                ->order_by('id', 'DESC')
                ->limit(1)
                ->get()
                ->row();

            // ✅ Step 7: Build response
            $data = [
                'customer_id'      => $customer->customer_id,
                'customer_name'    => $customer->customer_name ?? '',
                'customer_email'   => $customer->customer_email ?? '',
                'customer_mobile'  => $customer->customer_mobile ?? '',
                'commision_value'  => $commission->commision_value ?? null,
                'comission_type'   => $commission->comission_type ?? null,
                'permission_level' => $view_sensitive_data ? 'write' : 'read'
            ];

            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(200)
                ->set_output(json_encode([
                    'status'  => 'success',
                    'message' => 'Customer commission retrieved successfully.',
                    'data'    => $data
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        } catch (Exception $e) {
            return $this->_server_error('Unexpected server error: ' . $e->getMessage());
        }
    }

    public function warehouse_list()
    {
        try {
            log_message('debug', 'API Request: warehouse_list');

            // ✅ Step 1: Authenticate First-layer Token
            $user = $this->authenticate_token();
            if (!$user) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(401)
                    ->set_output(json_encode([
                        'status'  => 'unauthorized',
                        'message' => 'Unauthorized. Please provide a valid access token.'
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            }

            // ✅ Step 2: Validate Second-layer Token
            $second_token = $this->input->get_request_header('X-Second-Token');
            if (!$second_token) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(401)
                    ->set_output(json_encode([
                        'status'  => 'unauthorized',
                        'message' => 'Missing required second-layer authentication token (X-Second-Token).'
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            }

            try {
                $decoded = JWT::decode($second_token, new Key($this->jwt_key, $this->jwt_algo));
                if (!isset($decoded->customer_id)) {
                    return $this->_bad_request('Second-layer token is invalid or missing customer_id.');
                }
            } catch (Exception $e) {
                return $this->_bad_request('Second-layer token decode failed: ' . $e->getMessage());
            }

            // ✅ Step 3: Load Warehouse Model
            $this->load->model('warehouse/Warehouse_model', 'warehouse_model');

            // ✅ Step 4: Get Active Warehouses
            $warehouses = $this->warehouse_model->get_all_warehouses_by_status();

            if (!empty($warehouses)) {
                log_message('debug', 'Warehouses retrieved: ' . json_encode($warehouses));
                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(200)
                    ->set_output(json_encode([
                        'status'     => 'success',
                        'message'    => 'Warehouses retrieved successfully.',
                        'warehouses' => $warehouses
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            } else {
                return $this->_bad_request('No active warehouses found.');
            }

        } catch (Exception $e) {
            return $this->_server_error('Unexpected server error: ' . $e->getMessage());
        }
    }

    public function delete_customer()
    {
        try {
            log_message('debug', 'API Request: delete_customer');

            // ✅ Step 1: First-layer token authentication
            $user = $this->authenticate_token();
            if (!$user) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(401)
                    ->set_output(json_encode([
                        'status'  => 'unauthorized',
                        'message' => 'Unauthorized. Please provide a valid access token.'
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            }

            // ✅ Step 2: Second-layer token validation
            $second_token = $this->input->get_request_header('X-Second-Token');
            if (!$second_token) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(401)
                    ->set_output(json_encode([
                        'status'  => 'unauthorized',
                        'message' => 'Missing required second-layer authentication token (X-Second-Token).'
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            }

            try {
                $decoded = JWT::decode($second_token, new Key($this->jwt_key, $this->jwt_algo));
                if (!isset($decoded->customer_id)) {
                    return $this->_bad_request('Second-layer token is invalid or missing customer_id.');
                }
            } catch (Exception $e) {
                return $this->_bad_request('Second-layer token decode failed: ' . $e->getMessage());
            }

            // ✅ Step 3: Validate input
            $customer_email = $this->input->get('customer_email', TRUE);
            if (empty($customer_email)) {
                return $this->_bad_request('Missing required parameter: customer_email');
            }

            // ✅ Step 4: Fetch customer record
            $customer = $this->db->get_where('customer_information', ['customer_email' => $customer_email])->row();
            if (!$customer) {
                return $this->_bad_request('Customer not found with the provided email.');
            }

            // ✅ Step 5: Update status to 2 (deleted)
            $data = ['status' => 2];
            if ($this->Api_model->update_customer($data, $customer_email)) {

                $customer_id     = $customer->customer_id;
                $existing_name   = $customer->customer_name;
                $existing_email  = $customer->customer_email;
                $fcm_token       = $customer->fcm_token ?? null;
                $status_text     = 'Deleted';

                // ✅ Load libraries
                $this->load->library('sendmail_lib');
                $this->load->library('fcm_lib');

                // ✅ Notify Admins from user_login table
                $admin_subject = "Customer Status Updated to Deleted by User";
                $admin_message = "
                    <h3>Status Change Notification</h3>
                    <p>The status for customer <strong>{$existing_name}</strong> (ID: {$customer_id}) has been updated to <strong>{$status_text}</strong>.</p>
                ";

                $admin_query = $this->db->select('username')
                                        ->from('user_login')
                                        ->where(['user_type' => 1, 'status' => 1])
                                        ->get();

                foreach ($admin_query->result() as $admin) {
                    $admin_email = $admin->username;
                    if (filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
                        $this->sendmail_lib->send(
                            $admin_email,
                            $admin_subject,
                            $admin_message,
                            'noreply@hostelevate.com',
                            'DeshiShad Alert System'
                        );
                    }
                }

                // ✅ Notify Customer (Email)
                $customer_subject = "Your DeshiShad Account is Deleted by You";
                $customer_message = "
                    <h3>Dear {$existing_name},</h3>
                    <p>You have successfully <strong>deleted</strong> your DeshiShad account.</p>
                    <p>If you did not request this or need to reactivate your account, please contact DeshiShad Support immediately.</p>
                    <p>We’re sorry to see you go. Thank you for being with DeshiShad.</p>";
                $notification_body = "You have deleted your DeshiShad account.";

                $this->sendmail_lib->send(
                    $existing_email,
                    $customer_subject,
                    $customer_message,
                    'noreply@hostelevate.com',
                    'DeshiShad'
                );

                // ✅ FCM Push Notification
                if (!empty($fcm_token)) {
                    $this->fcm_lib->sendNotification($fcm_token, $customer_subject, $notification_body);
                }

                log_message('info', "📤 Status deleted and notification sent for customer_id={$customer_id}");

                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(200)
                    ->set_output(json_encode([
                        'status'     => 'success',
                        'message'    => 'Customer status updated to deleted',
                        'permission' => 'read'
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            } else {
                return $this->_server_error('Failed to update customer. Please try again.');
            }

        } catch (Exception $e) {
            return $this->_server_error('Unexpected server error: ' . $e->getMessage());
        }
    }

    public function get_payment_methods()
    {
        try {
            // ✅ Step 1: Authenticate First-layer Token
            $user = $this->authenticate_token();
            if (!$user) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(401)
                    ->set_output(json_encode([
                        'status'  => 'unauthorized',
                        'message' => 'Unauthorized. Please provide a valid access token.'
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            }

            // ✅ Step 2: Log Headers
            log_message('info', 'Headers: ' . json_encode(getallheaders()));

            // ✅ Step 3: Validate Second-layer Token
            $second_token = $this->input->get_request_header('X-Second-Token');

            if (!$second_token) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(401)
                    ->set_output(json_encode([
                        'status'  => 'unauthorized',
                        'message' => 'Missing required second-layer authentication token (X-Second-Token).'
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            }

            try {
                $decoded = JWT::decode($second_token, new Key($this->jwt_key, $this->jwt_algo));
                if (!isset($decoded->customer_id)) {
                    return $this->_bad_request('Second-layer token is invalid or missing customer_id.');
                }
            } catch (Exception $e) {
                return $this->_bad_request('Second-layer token decode failed: ' . $e->getMessage());
            }

            // ✅ Step 4: Fetch Payment Methods
            $data = $this->db->select('HeadName, HeadCode')
                ->from('acc_coa')
                ->group_start()
                    ->where('PHeadName', 'Cash')
                    ->or_where('PHeadName', 'Cash at Bank')
                ->group_end()
                ->get()
                ->result();

            // ✅ Step 5: Build Response
            if (!empty($data)) {
                $list[] = [
                    'HeadCode' => '0',
                    'HeadName' => 'Credit Sale'
                ];

                foreach ($data as $value) {
                    $list[] = [
                        'HeadCode' => $value->HeadCode,
                        'HeadName' => $value->HeadName
                    ];
                }

                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(200)
                    ->set_output(json_encode([
                        'status'          => 'success',
                        'message'         => 'Payment methods fetched successfully.',
                        'payment_methods' => $list
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            } else {
                return $this->_bad_request('No payment methods found.');
            }

        } catch (Exception $e) {
            return $this->_server_error('Unexpected server error: ' . $e->getMessage());
        }
    }

    public function customer_password_change()
    {
        try {
            $user = $this->authenticate_token();
            if (!$user) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(401)
                    ->set_output(json_encode([
                        'status'  => 'unauthorized',
                        'message' => 'Unauthorized. Please provide a valid access token.'
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            }

            $second_token = $this->input->get_request_header('X-Second-Token');
            if (!$second_token) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(401)
                    ->set_output(json_encode([
                        'status'  => 'unauthorized',
                        'message' => 'Missing required second-layer authentication token (X-Second-Token).'
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            }

            try {
                $decoded = JWT::decode($second_token, new Key($this->jwt_key, $this->jwt_algo));
                if (!isset($decoded->customer_id)) {
                    return $this->_bad_request('Second-layer token is invalid or missing customer_id.');
                }
                $customer_id = $decoded->customer_id;
            } catch (Exception $e) {
                return $this->_bad_request('Second-layer token decode failed: ' . $e->getMessage());
            }

            $input = json_decode(trim(file_get_contents("php://input")), true);
            log_message('debug', '[customer_password_change] 🔐 Input: ' . json_encode($input));

            if (!isset($input['old_password']) || !isset($input['new_password'])) {
                return $this->_bad_request('Both old_password and new_password are required.');
            }

            $old_password = trim($input['old_password']);
            $new_password = trim($input['new_password']);

            if (strlen($new_password) < 6) {
                return $this->_bad_request('New password must be at least 6 characters long.');
            }

            $auth = $this->db->get_where('customer_auth', ['customer_id' => $customer_id])->row();
            if (!$auth || !password_verify($old_password, $auth->password)) {
                return $this->_bad_request('Old password is incorrect.');
            }

            $hashed_new_password = password_hash($new_password, PASSWORD_BCRYPT);
            $this->db->where('customer_id', $customer_id)->update('customer_auth', [
                'password'    => $hashed_new_password,
                'updated_at'  => date('Y-m-d H:i:s')
            ]);
            log_message('debug', "[customer_password_change] 🔁 Password updated for customer_id={$customer_id}");

            $customer_info = $this->db->get_where('customer_information', ['customer_id' => $customer_id])->row();
            $customer_name = $customer_info->customer_name ?? 'Unknown';
            $customer_email = $customer_info->customer_email ?? '';

            $smtp = $this->db->get('email_config')->row_array();
            if (empty($smtp)) {
                log_message('error', '[customer_password_change] ❌ SMTP config missing');
            }

            $this->load->library('Sendmail_lib');

            // Notify Admins
            $admin_subject = "🔐 Customer Password Changed - {$customer_name}";
            $admin_message = "
                <h4>Password Change Notification</h4>
                <p><strong>Name:</strong> {$customer_name}</p>
                <p><strong>Email:</strong> {$customer_email}</p>
                <p><strong>Customer ID:</strong> {$customer_id}</p>
                <p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>
            ";

            $admins = $this->db->select('username')->from('user_login')->where(['user_type' => 1, 'status' => 1])->get();
            foreach ($admins->result() as $admin) {
                if (filter_var($admin->username, FILTER_VALIDATE_EMAIL)) {
                    $this->sendmail_lib->send(
                        $admin->username,
                        $admin_subject,
                        $admin_message,
                        $smtp['smtp_user'],
                        'DeshiShad Alert System'
                    );
                }
            }

            // Notify Customer
            if (!empty($customer_email)) {
                $customer_subject = "🛡️ Your Password Has Been Updated";
                $customer_message = "
                    <h3>Hello {$customer_name},</h3>
                    <p>Your account password has been successfully updated.</p>
                    <p><strong>New Password:</strong> {$new_password}</p>
                    <p>If you did not make this change, please contact our support immediately.</p>
                ";

                $this->sendmail_lib->send(
                    $customer_email,
                    $customer_subject,
                    $customer_message,
                    $smtp['smtp_user'],
                    'DeshiShad Support'
                );
            }

            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(200)
                ->set_output(json_encode([
                    'status'  => 'success',
                    'message' => 'Password updated and notifications sent.'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        } catch (Exception $e) {
            log_message('error', '[customer_password_change] ❌ Exception: ' . $e->getMessage());
            return $this->_server_error('Unexpected server error: ' . $e->getMessage());
        }
    }

    public function insert_sale()
    {
        header('Content-Type: application/json');
        log_message('debug', '✅ API insert_sale called');

        // ✅ First-layer authentication
        $user = $this->authenticate_token();
        if (!$user) {
            echo json_encode(['status' => 'unauthorized', 'message' => 'Unauthorized. Bearer token missing or invalid.']);
            return;
        }

        // ✅ Second-layer authentication
        $second_token = $this->input->get_request_header('X-Second-Token');
        if (!$second_token) {
            echo json_encode(['status' => 'unauthorized', 'message' => 'Missing second-layer token (X-Second-Token).']);
            return;
        }

        try {
            $decoded = JWT::decode($second_token, new Key($this->jwt_key, $this->jwt_algo));
            if (!isset($decoded->customer_id)) {
                echo json_encode(['status' => 'error', 'message' => 'Second-layer token invalid.']);
                return;
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Second-layer token decode failed: ' . $e->getMessage()]);
            return;
        }

        // ✅ Parse input
        $input = json_decode(file_get_contents("php://input"), true);
        log_message('debug', '🧾 Raw request body: ' . print_r($input, true));

        if (empty($input)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input']);
            return;
        }

        // ✅ Setup session and models
        $this->load->library(['session', 'occational', 'smsgateway']);
        $this->load->model('invoice/Invoice_model', 'invoice_model');
        $this->load->model('account/Accounts_model', 'accounts_model');

        // ✅ Set session for voucher approval
        $createby = $input['createby'] ?? null;
        $this->session->set_userdata('id', $createby);

        // ✅ Financial year check
        $finyear = financial_year();
        if ($finyear <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Please Create Financial Year First']);
            return;
        }

        // ✅ Generate invoice ID
        $invoice_id = $this->invoice_generator();
        log_message('debug', "🧾 Generated invoice_id = $invoice_id");

        // ✅ Prepare $_POST for form-compatible logic
        $_POST = [
            'invoice_id'           => $invoice_id,
            'invoice'              => $invoice_id,
            'customer_id'          => $input['customer_id'],
            'paid_amount'          => $input['paid_amount'],
            'due_amount'           => $input['due_amount'] ?? 0,
            'total_discount'       => $input['total_discount'] ?? 0,
            'total_tax'            => $input['total_tax'] ?? 0,
            'invoice_date'         => $input['invoice_date'] ?? date('Y-m-d'),
            'inva_details'         => $input['inva_details'] ?? 'API Invoice',
            'payment_type'         => $input['payment_type'],
            'delivery_note'        => $input['delivery_note'] ?? '',
            'status'               => $input['status'] ?? 1,
            'invoice_discount'     => 0,
            'total_vat_amnt'       => 0,
            'previous'             => 0,
            'shipping_cost'        => 0,
            'is_credit'            => ($input['payment_type'] == 0) ? 1 : 0,
            'multipaytype'         => [$input['payment_type']],
            'pamount_by_method'    => [$input['paid_amount']],
            'product_id'           => [],
            'product_quantity'     => [],
            'product_rate'         => [],
            'serial_no'            => [],
            'total_price'          => [],
            'discount'             => [],
            'discountvalue'        => [],
            'vatvalue'             => [],
            'vatpercent'           => [],
            'desc'                 => [],
            'available_quantity'   => [],
        ];

        $grand_total = 0;
        foreach ($input['detailsinfo'] as $item) {
            $qty = floatval($item['product_quantity']);
            $rate = floatval($item['product_rate']);
            $total = $qty * $rate;
            $grand_total += $total;

            $_POST['product_id'][]         = $item['product_id'];
            $_POST['product_quantity'][]   = $qty;
            $_POST['product_rate'][]       = $rate;
            $_POST['serial_no'][]          = $item['serial_no'] ?? '';
            $_POST['total_price'][]        = $total;
            $_POST['discount'][]           = 0;
            $_POST['discountvalue'][]      = 0;
            $_POST['vatvalue'][]           = 0;
            $_POST['vatpercent'][]         = 0;
            $_POST['desc'][]               = '';
            $_POST['available_quantity'][] = $qty + 100;
        }

        $_POST['grand_total_price'] = $grand_total;

        // ✅ Insert invoice
        $inserted_invoice_id = $this->invoice_model->invoice_entry($invoice_id);
        log_message('debug', "✅ Invoice inserted, ID: $inserted_invoice_id");

        // ✅ Auto approve if enabled
        $setting_data = $this->db->select('is_autoapprove_v')->from('web_setting')->where('setting_id', 1)->get()->row();
        if ($setting_data && $setting_data->is_autoapprove_v == 1) {
            $this->autoapprove($inserted_invoice_id);
            log_message('debug', "✅ Auto-approved voucher for $inserted_invoice_id");
        }

        // ✅ Optional SMS
        $config_data = $this->db->get('sms_settings')->row();
        if ($config_data->isinvoice == 1) {
            $cusinfo = $this->db->get_where('customer_information', ['customer_id' => $input['customer_id']])->row();
            if (!empty($cusinfo)) {
                $message = 'Mr.' . $cusinfo->customer_name . ', You have purchased ' . number_format($grand_total, 2) . ' and paid ' . $_POST['paid_amount'];
                $this->smsgateway->send([
                    'apiProvider' => 'nexmo',
                    'username'    => $config_data->api_key,
                    'password'    => $config_data->api_secret,
                    'from'        => $config_data->from,
                    'to'          => $cusinfo->customer_mobile,
                    'message'     => $message
                ]);
                log_message('debug', "✅ SMS sent to customer {$cusinfo->customer_mobile}");
            }
        }

        // ✅ Send Email Notifications to Admin and Customer
        $this->load->library('Sendmail_lib');
        $smtp = $this->db->get('email_config')->row_array();

        $cusinfo = $this->db->get_where('customer_information', ['customer_id' => $input['customer_id']])->row();
        $customer_name = $cusinfo->customer_name ?? 'Unknown';
        $customer_email = $cusinfo->customer_email ?? '';

        $admin_subject = "🧾 New Invoice Created - {$invoice_id}";
        $admin_message = "<h4>New Invoice Notification</h4><p><strong>Invoice ID:</strong> {$invoice_id}</p><p><strong>Customer:</strong> {$customer_name}</p><p><strong>Email:</strong> {$customer_email}</p><p><strong>Total Amount:</strong> " . number_format($grand_total, 2) . "</p><p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

        $admins = $this->db->select('username')->from('user_login')->where(['user_type' => 1, 'status' => 1])->get();
        foreach ($admins->result() as $admin) {
            if (filter_var($admin->username, FILTER_VALIDATE_EMAIL)) {
                $this->sendmail_lib->send(
                    $admin->username,
                    $admin_subject,
                    $admin_message,
                    $smtp['smtp_user'],
                    'DeshiShad ERP System'
                );
            }
        }

        if (!empty($customer_email)) {
            $customer_subject = "🧾 Your Invoice [{$invoice_id}] Has Been Created";
            $customer_message = "<h3>Dear {$customer_name},</h3><p>Thank you for your purchase. Your invoice has been created successfully.</p><p><strong>Invoice ID:</strong> {$invoice_id}</p><p><strong>Total:</strong> " . number_format($grand_total, 2) . "</p><p>If you have any questions, please contact support.</p>";

            $this->sendmail_lib->send(
                $customer_email,
                $customer_subject,
                $customer_message,
                $smtp['smtp_user'],
                'DeshiShad Sales'
            );
        }

        echo json_encode([
            'status'     => 'success',
            'invoice_id' => $invoice_id,
            'message'    => 'Invoice created successfully via authenticated API'
        ]);
    }

    public function invoice_generator() {
        $this->db->select_max('invoice', 'invoice_no');
        $query = $this->db->get('invoice');
        $result = $query->result_array();
        $invoice_no = $result[0]['invoice_no'];

        if ($invoice_no != '') {
            $invoice_no = $invoice_no + 1;
        } else {
            $invoice_no = 1000;
        }
        return $invoice_no;
    }

    public function autoapprove($invoice_id){
        $this->load->model('account/Accounts_model', 'accounts_model');
        
        $vouchers = $this->db->select('referenceNo, VNo')->from('acc_vaucher')
                      ->where('referenceNo', $invoice_id)
                      ->where('status', 0)
                      ->get()->result();
        
        log_message('debug', '🎯 Vouchers to approve: ' . json_encode($vouchers));
    
        foreach ($vouchers as $value) {
            log_message('debug', '🟢 Approving voucher: VNo=' . $value->VNo . ', Ref=' . $value->referenceNo);
            $result = $this->accounts_model->approved_vaucher($value->VNo, 'active');
            log_message('debug', '✅ Voucher approved: ' . json_encode($result));
        }
    
        return true;
    }

    public function product_search()
    {
        try {
            $start_time = microtime(true);
            log_message('debug', 'product_search() initiated');

            // ✅ First-layer token check
            $user = $this->authenticate_token();
            if (!$user) {
                return $this->_unauthorized('Unauthorized. Please provide a valid access token.');
            }

            // ✅ Second-layer token (optional)
            $second_token = $this->input->get_request_header('X-Second-Token');
            $view_sensitive_data = false;
            if ($second_token) {
                try {
                    $decoded = JWT::decode($second_token, new Key($this->jwt_key, $this->jwt_algo));
                    $view_sensitive_data = isset($decoded->customer_id);
                    log_message('info', 'Second Layer Token Decoded Successfully for customer: ' . $decoded->customer_id);
                } catch (Exception $e) {
                    log_message('error', 'Second layer token error: ' . $e->getMessage());
                }
            }

            // ✅ Input validation using POST
            $params = [
                'product_id'   => $this->input->post('product_id', TRUE) ?? '',
                'product_name' => $this->input->post('product_name', TRUE) ?? '',
                'category_id'  => $this->input->post('category_id', TRUE) ?? '',
                'min_price'    => $this->input->post('min_price', TRUE),
                'max_price'    => $this->input->post('max_price', TRUE)
            ];
            $limit  = (int) ($this->input->post('limit', TRUE) ?? 10);
            $page   = (int) ($this->input->post('page', TRUE) ?? 1);
            $offset = ($page - 1) * $limit;

            // ✅ Search logic
            $total_count = $this->countProductSearchResults($params);
            $products = $this->getProductSearchResults($params, $limit, $offset);

            log_message('debug', 'Fetched product count: ' . count($products));

            // ✅ Category mapping
            $category_map = [];
            $categories = $this->db->select('category_id, category_name')->get('product_category')->result();
            foreach ($categories as $cat) {
                $category_map[$cat->category_id] = $cat->category_name;
            }

            // ✅ Data post-processing
            foreach ($products as $k => $v) {
                if (!empty($products[$k]['image'])) {
                    $products[$k]['image'] = base_url(str_replace('./', '', $products[$k]['image']));
                }

                $products[$k]['category_name'] = $category_map[$v['category_id']] ?? 'Uncategorized';

                if (!$view_sensitive_data) {
                    unset($products[$k]['price']);
                } else {
                    $products[$k]['price'] = (float)$products[$k]['price'];
                }

                $products[$k]['qr_code'] = base_url('my-assets/image/qr/' . $v['product_id'] . '.png');
                $products[$k]['bar_code'] = base_url('Cbarcode/barcode_generator/' . $v['product_id']);
            }

            $execution_time = microtime(true) - $start_time;

            // ✅ Return structured success response
            return $this->_success([
                'total_count'    => $total_count,
                'matched_count'  => count($products),
                'page'           => $page,
                'page_count'     => ceil($total_count / $limit),
                'execution_time' => round($execution_time, 4),
                'limit'          => $limit,
                'result'         => $products
            ], 'Product search completed successfully.');

        } catch (Exception $e) {
            return $this->_server_error('product_search() failed: ' . $e->getMessage());
        }
    }

    public function buildProductSearchQuery(array $params)
    {
        $this->db->from('product_information');

        if (!empty($params['product_id'])) {
            $this->db->where('product_information.product_id', $params['product_id']);
        }

        if (!empty($params['product_name'])) {
            $this->db->like('product_information.product_name', $params['product_name']);
        }

        if (!empty($params['category_id'])) {
            $this->db->where('product_information.category_id', $params['category_id']);
        }

        if (!is_null($params['min_price'])) {
            $this->db->where('product_information.price >=', $params['min_price']);
        }

        if (!is_null($params['max_price'])) {
            $this->db->where('product_information.price <=', $params['max_price']);
        }

        return clone $this->db; // Optional: clone here to avoid mutation if reused multiple times
    }

    // ✅ Result query logic
    public function getProductSearchResults($params, $limit, $offset)
    {
        $this->db->from('product_information');

        if (!empty($params['product_id'])) {
            $this->db->where('product_information.product_id', $params['product_id']);
        }

        if (!empty($params['product_name'])) {
            $this->db->like('product_information.product_name', $params['product_name']);
        }

        if (!empty($params['category_id'])) {
            $this->db->where('product_information.category_id', $params['category_id']);
        }

        if (!is_null($params['min_price'])) {
            $this->db->where('product_information.price >=', $params['min_price']);
        }

        if (!is_null($params['max_price'])) {
            $this->db->where('product_information.price <=', $params['max_price']);
        }

        $this->db->limit($limit, $offset);
        return $this->db->get()->result_array();
    }

    // ✅ Count query logic
    public function countProductSearchResults($params)
    {
        $this->db->from('product_information');

        if (!empty($params['product_id'])) {
            $this->db->where('product_information.product_id', $params['product_id']);
        }

        if (!empty($params['product_name'])) {
            $this->db->like('product_information.product_name', $params['product_name']);
        }

        if (!empty($params['category_id'])) {
            $this->db->where('product_information.category_id', $params['category_id']);
        }

        if (!is_null($params['min_price'])) {
            $this->db->where('product_information.price >=', $params['min_price']);
        }

        if (!is_null($params['max_price'])) {
            $this->db->where('product_information.price <=', $params['max_price']);
        }

        return $this->db->count_all_results(); // executes and resets
    }

    private function get_all_related_category_ids($category_id)
    {
        // Validate input
        $category_id = (int)$category_id;
        if ($category_id <= 0) {
            return [];
        }

        // Get all active categories with parent relationships
        $this->db->select('category_id, parent_id')
                ->from('product_category')
                ->where('status', 1); // Only active categories

        $query = $this->db->get();
        if (!$query) {
            log_message('error', 'Failed to fetch categories: ' . $this->db->error()['message']);
            return [$category_id];
        }

        $all_categories = $query->result_array();
        $children_map = [];

        // Build children mapping
        foreach ($all_categories as $cat) {
            $parent_id = (int)$cat['parent_id'];
            if ($parent_id > 0) { // Only if has valid parent
                if (!isset($children_map[$parent_id])) {
                    $children_map[$parent_id] = [];
                }
                $children_map[$parent_id][] = (int)$cat['category_id'];
            }
        }

        // Find all descendants using BFS
        $all_ids = [$category_id];
        $queue = [$category_id];

        while (!empty($queue)) {
            $current = array_shift($queue);
            
            if (isset($children_map[$current])) {
                foreach ($children_map[$current] as $child_id) {
                    if (!in_array($child_id, $all_ids)) {
                        $all_ids[] = $child_id;
                        $queue[] = $child_id;
                    }
                }
            }
        }

        log_message('debug', 'Found ' . count($all_ids) . ' related categories for ID ' . $category_id);
        return $all_ids;
    }

    

    /**
         * @OA\Post(
         *     path="/apiv2/insert_customer",
         *     tags={"Customer"},
         *     summary="Insert a new customer",
         *     description="Adds a new customer and creates login credentials. Requires a Bearer access token.",
         *     security={{"bearerAuth":{}}},
         *     @OA\RequestBody(
         *         required=true,
         *         @OA\MediaType(
         *             mediaType="multipart/form-data",
         *             @OA\Schema(
         *                 required={"customer_name", "mobile", "password"},
         *                 @OA\Property(property="customer_name", type="string", example="John Doe"),
         *                 @OA\Property(property="address", type="string", example="123 Street"),
         *                 @OA\Property(property="address2", type="string", example="Suite 500"),
         *                 @OA\Property(property="mobile", type="string", example="01710000000"),
         *                 @OA\Property(property="email", type="string", example="john@example.com"),
         *                 @OA\Property(property="email_address", type="string", example="billing@example.com"),
         *                 @OA\Property(property="contact", type="string", example="Jane Smith"),
         *                 @OA\Property(property="phone", type="string", example="09666000000"),
         *                 @OA\Property(property="fax", type="string", example="0881234567"),
         *                 @OA\Property(property="city", type="string", example="Dhaka"),
         *                 @OA\Property(property="state", type="string", example="Gulshan"),
         *                 @OA\Property(property="zip", type="string", example="1212"),
         *                 @OA\Property(property="country", type="string", example="Bangladesh"),
         *                 @OA\Property(property="sales_permit", type="file", description="Upload sales permit"),
         *                 @OA\Property(property="sales_permit_number", type="string", example="PERMIT-12345"),
         *                 @OA\Property(property="previous_balance", type="number", format="float", example="1500.50"),
         *                 @OA\Property(property="password", type="string", format="password", example="secure123")
         *             )
         *         )
         *     ),
         *     @OA\Response(
         *         response=200,
         *         description="Customer successfully added",
         *         @OA\JsonContent(
         *             @OA\Property(property="response", type="object",
         *                 @OA\Property(property="status", type="string", example="ok"),
         *                 @OA\Property(property="message", type="string", example="Successfully Added"),
         *                 @OA\Property(property="permission", type="string", example="write")
         *             )
         *         )
         *     ),
         *     @OA\Response(
         *         response=401,
         *         description="Unauthorized - Missing or invalid token"
         *     ),
         *     @OA\Response(
         *         response=400,
         *         description="File upload or validation failed"
         *     )
         * )
     */


    public function insert_customer()
    {
        log_message('debug', '🔐 Checking authentication...');
        $user = $this->authenticate_token();
        if (!$user) {
            log_message('error', '❌ Unauthorized access attempt');
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(401)
                ->set_output(json_encode([
                    'status' => 'error',
                    'message' => 'Unauthorized. Please provide a valid access token.'
                ]));
        }

        $customer_email  = $this->input->post('email');
        $customer_mobile = $this->input->post('mobile');
        $password        = $this->input->post('password');

        log_message('debug', "📥 Email: $customer_email | Mobile: $customer_mobile");

        if (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
            return $this->_bad_request('Invalid email format');
        }

        if (!preg_match('/^[0-9]+$/', $customer_mobile)) {
            return $this->_bad_request('Mobile must be digits only');
        }

        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            return $this->_bad_request('Password must include at least one special character');
        }

        if ($this->db->where('customer_mobile', $customer_mobile)->get('customer_information')->row()) {
            return $this->_conflict('Mobile number already exists');
        }

        if ($this->db->where('username', $customer_email)->get('customer_auth')->row()) {
            return $this->_conflict('Email already registered');
        }

        $sales_permit = '';
        if (!empty($_FILES['sales_permit']['name'])) {
            $sales_permit = $this->_upload_file('sales_permit', './uploads/sales_permits/');
            if ($sales_permit === false) return;
        }

        $data = [
            'customer_name'        => $this->input->post('customer_name'),
            'customer_address'     => $this->input->post('address'),
            'address2'             => $this->input->post('address2'),
            'customer_mobile'      => $customer_mobile,
            'customer_email'       => $customer_email,
            'email_address'        => $this->input->post('email_address'),
            'contact'              => $this->input->post('contact'),
            'phone'                => $this->input->post('phone'),
            'fax'                  => $this->input->post('fax'),
            'city'                 => $this->input->post('city'),
            'state'                => $this->input->post('state'),
            'zip'                  => $this->input->post('zip'),
            'country'              => $this->input->post('country'),
            'sales_permit'         => $sales_permit,
            'sales_permit_number'  => $this->input->post('sales_permit_number'),
            'status'               => 3,
            'create_date'          => date('Y-m-d H:i:s'),
            'create_by'            => $user->uid
        ];

        if ($this->Api_model->customer_create($data)) {
            $customer_id = $this->db->insert_id();
            $headcode    = ($coa = $this->Api_model->customerheadcode()) ? $coa->HeadCode + 1 : "102030000001";
            $c_acc       = $customer_id . '-' . $this->input->post('customer_name');

            $this->db->insert('acc_coa', [
                'HeadCode' => $headcode,
                'HeadName' => $c_acc,
                'PHeadName' => 'Merchant Receivable',
                'HeadLevel' => '4',
                'IsActive' => '1',
                'IsTransaction' => '1',
                'IsGL' => '0',
                'customer_id' => $customer_id,
                'HeadType' => 'A',
                'IsBudget' => '0',
                'IsDepreciation' => '0',
                'DepreciationRate' => '0',
                'CreateBy' => $user->uid,
                'CreateDate' => date('Y-m-d H:i:s')
            ]);

            $this->Api_model->customer_previous_balance_add($this->input->post('previous_balance'), $customer_id);

            $this->db->insert('customer_auth', [
                'customer_id' => $customer_id,
                'username'    => $customer_email,
                'password'    => password_hash($password, PASSWORD_BCRYPT),
                'status'      => 3
            ]);

            $token = bin2hex(random_bytes(32));
            $this->db->insert('email_verification_tokens', [
                'customer_id' => $customer_id,
                'token'       => $token
            ]);

            $this->session->set_userdata('registered_customer_id', $customer_id);
            $this->session->set_userdata('registered_customer_email', $customer_email);

            $verify_url = base_url("apiv2/verify_email?token=$token");

            // ✅ Sendmail_lib usage for all mail communications
            $this->load->library('sendmail_lib');

            // Send verification mail
            $this->sendmail_lib->send(
                $customer_email,
                'Verify your email address',
                "<h3>Registration Successful!</h3>
                <p>Thank you for registering. Please click the button below to verify your email:</p>
                <p><a href='$verify_url' style='padding:10px 20px; background:#4CAF50; color:#fff;'>Verify Email</a></p>",
                'noreply@hostelevate.com',
                'DeshiShad'
            );

            // Get creator info
            $creator = $this->db->where('id', $user->uid)->get('api_users')->row();
            $creator_text = 'Unknown';
            if ($creator) {
                $creator_text = $creator->usertype === 'webuser' ? 'Customer (via Web Portal)' :
                                ($creator->usertype === 'appuser' ? 'Customer (via Mobile App)' :
                                ucfirst($creator->usertype));
            }

            // Notify admins
            $admin_query = $this->db->where_in('id', [1, 2])->get('user_login');
            foreach ($admin_query->result() as $admin) {
                $this->sendmail_lib->send(
                    $admin->username,
                    'New Customer Registered',
                    "<h4>New customer registration alert</h4>
                    <p><strong>Name:</strong> {$this->input->post('customer_name')}</p>
                    <p><strong>Email:</strong> $customer_email</p>
                    <p><strong>Mobile:</strong> $customer_mobile</p>
                    <p><strong>Who Created:</strong> $creator_text</p>
                    <p><strong>Registered At:</strong> " . date('Y-m-d H:i:s') . "</p>",
                    'noreply@hostelevate.com',
                    'DeshiShad Alert System'
                );
            }

            return $this->output->set_content_type('application/json')->set_output(json_encode([
                'response' => [
                    'status'     => 'ok',
                    'message'    => 'Customer created. Verification email sent.',
                    'permission' => 'write'
                ]
            ]));
        } else {
            return $this->_server_error('Customer insertion failed. Please try again.');
        }
    }


    public function verify_email()
    {
        $token = $this->input->get('token');
        if (!$token) {
            show_error('Invalid verification link', 400);
        }

        // 🔍 Lookup the token record
        $record = $this->db->get_where('email_verification_tokens', ['token' => $token])->row();
        if (!$record) {
            show_error('Token not found or expired', 404);
        }

        // ✅ Update both tables: set status = 0 (active)
        $this->db->where('customer_id', $record->customer_id)->update('customer_information', ['status' => 0]);
        $this->db->where('customer_id', $record->customer_id)->update('customer_auth', ['status' => 0]);

        // 🧹 Optional: delete or mark token as used
        // $this->db->delete('email_verification_tokens', ['token' => $token]);

        // ✅ Get customer info for confirmation email
        $customer = $this->db->get_where('customer_information', ['customer_id' => $record->customer_id])->row();

        // 📧 Build email message
        $email_message = "
            <h3>Email Verified!</h3>
            <p>You have successfully verified your email.</p>
            <p>Our support team will now contact you to activate your account.</p>
            <p>You may also call us at <strong>+1234567890012</strong>.</p>
        ";

        // 📬 Send confirmation email
        $this->load->library('sendmail_lib');
        log_message('debug', "📨 Sending verification confirmation to: {$customer->customer_email}");

        $this->sendmail_lib->send(
            $customer->customer_email,
            'Email Verified Successfully',
            $email_message,
            'noreply@hostelevate.com',
            'DeshiShad'
        );

        // ✅ Show confirmation in browser
        echo "<h2>Email Verified</h2><p>Your email has been verified. Our support team will contact you shortly.</p><p>If needed, call <strong>+1234567890012</strong>.</p>";
    }

    public function second_layer_login()
    {
        try {
            // ✅ First-layer token authentication (Bearer token)
            $user = $this->authenticate_token();
            if (!$user) {
                return $this->_unauthorized('Unauthorized. Bearer token required for 2nd layer login.');
            }

            // ✅ Parse JSON input
            $input_raw = file_get_contents("php://input");
            log_message('debug', '🔍 Raw second_layer_login input: ' . $input_raw);
            $input = json_decode(trim($input_raw), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->_bad_request('Invalid JSON input: ' . json_last_error_msg());
            }

            // ✅ Required fields validation
            if (empty($input['username']) || empty($input['password']) || empty($input['fcm_token'])) {
                return $this->_bad_request('Username, password, and fcm_token are required.');
            }

            $username   = trim($input['username']);
            $password   = trim($input['password']);
            $fcm_token  = trim($input['fcm_token']);

            // ✅ Lookup in customer_auth
            $auth_user = $this->db->select('id, customer_id, password, status')
                ->from('customer_auth')
                ->where('username', $username)
                ->where('status', 1)
                ->get()
                ->row();

            if (!$auth_user) {
                return $this->_unauthorized('Invalid credentials or account not active.');
            }

            // ✅ Password verify
            if (!password_verify($password, $auth_user->password)) {
                return $this->_unauthorized('Invalid password.');
            }

            // ✅ Generate 2nd layer token (JWT)
            $payload = [
                'iat'         => time(),
                'exp'         => time() + 3600,
                'customer_id' => $auth_user->customer_id,
                'username'    => $username,
            ];
            $second_layer_token = JWT::encode($payload, $this->jwt_key, $this->jwt_algo);

            // ✅ Store FCM token
            $this->db->where('id', $auth_user->id)->update('customer_auth', [
                'fcm_token'  => $fcm_token,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            if ($this->db->affected_rows() === 0) {
                log_message('warning', '⚠️ FCM token update had no effect for user ID: ' . $auth_user->id);
            }

            // ✅ Send push notification
            $this->load->library('Fcm_lib');
            $this->fcm_lib->sendNotification(
                $fcm_token,
                'Welcome!',
                'You have successfully logged in to the 2nd layer.'
            );

            // ✅ Response
            return $this->_success([
                'second_layer_token' => $second_layer_token,
                'fcm_token'          => $fcm_token,
                'expires_in'         => 3600
            ], '2nd Layer Login Successful');

        } catch (ExpiredException $e) {
            return $this->_unauthorized('Bearer token expired. Please login again.');
        } catch (Exception $e) {
            log_message('error', '❌ second_layer_login() failed: ' . $e->getMessage());
            return $this->_server_error('Something went wrong during 2nd layer login.');
        }
    }


    public function get_customer_profile()
    {
        try {
            log_message('debug', '[GetProfile] Initiated profile fetch');

            // 🔐 First-layer token authentication
            $user = $this->authenticate_token();
            if (!$user || empty($user->uid)) {
                return $this->_unauthorized('Unauthorized. Please provide a valid access token.');
            }

            $customer_id = $user->uid;
            log_message('debug', "[GetProfile] Authenticated customer_id: {$customer_id}");

            // ✅ Fetch customer_information
            $customer = $this->db->get_where('customer_information', ['customer_id' => $customer_id])->row_array();
            if (!$customer) {
                return $this->_not_found('Customer profile not found.');
            }

            // ✅ Fetch customer_auth
            $auth = $this->db->select('username, status as auth_status, fcm_token')
                            ->get_where('customer_auth', ['customer_id' => $customer_id])
                            ->row_array();

            // ✅ Fetch latest commission
            $commission = $this->db->order_by('id', 'DESC')
                                ->get_where('customer_comission', ['customer_id' => $customer_id])
                                ->row_array();

            // 🧩 Merge and respond
            $profile = [
                'customer'   => $customer,
                'auth'       => $auth,
                'commission' => $commission
            ];

            return $this->_success($profile, 'Customer profile fetched successfully.');

        } catch (Exception $e) {
            log_message('error', '[GetProfile] Error: ' . $e->getMessage());
            return $this->_server_error('Failed to fetch profile.');
        }
    }

    private function _bad_request($message)
    {
        log_message('error', '❌ ' . $message);
        return $this->output->set_content_type('application/json')->set_status_header(400)
            ->set_output(json_encode(['status' => 'error', 'message' => $message]));
    }

    private function _conflict($message)
    {
        log_message('error', '❗ ' . $message);
        return $this->output->set_content_type('application/json')->set_status_header(409)
            ->set_output(json_encode(['status' => 'error', 'message' => $message]));
    }

    private function _server_error($message)
    {
        log_message('error', '❌ ' . $message);
        return $this->output->set_content_type('application/json')->set_status_header(500)
            ->set_output(json_encode(['status' => 'error', 'message' => $message]));
    }

    private function _upload_file($field_name, $upload_path)
    {
        $config = [
            'upload_path'   => $upload_path,
            'allowed_types' => 'jpg|jpeg|png|pdf|doc|docx',
            'max_size'      => 2048,
            'file_name'     => time() . '_' . $_FILES[$field_name]['name']
        ];

        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0755, true);
        }

        $this->load->library('upload', $config);
        if ($this->upload->do_upload($field_name)) {
            return $this->upload->data()['file_name'];
        } else {
            $error = strip_tags($this->upload->display_errors());
            $this->_bad_request('File upload failed: ' . $error);
            return false;
        }
    }

    /**
     * 200 OK
     */
    private function _success($data = [], $message = 'Success')
    {
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode([
                'status'  => 'success',
                'message' => $message,
                'data'    => $data
            ]));
    }

    /**
     * 201 Created
     */
    private function _created($data = [], $message = 'Resource created successfully')
    {
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(201)
            ->set_output(json_encode([
                'status'  => 'success',
                'message' => $message,
                'data'    => $data
            ]));
    }

    /**
     * 401 Unauthorized
     */
    private function _unauthorized($message = 'Unauthorized')
    {
        log_message('error', '🔒 ' . $message);
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(401)
            ->set_output(json_encode([
                'status'  => 'error',
                'message' => $message
            ]));
    }

    /**
     * 403 Forbidden
     */
    private function _forbidden($message = 'Forbidden')
    {
        log_message('error', '🚫 ' . $message);
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(403)
            ->set_output(json_encode([
                'status'  => 'error',
                'message' => $message
            ]));
    }

    /**
     * 404 Not Found
     */
    private function _not_found($message = 'Resource not found')
    {
        log_message('error', '❓ ' . $message);
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(404)
            ->set_output(json_encode([
                'status'  => 'error',
                'message' => $message
            ]));
    }

    /**
     * 422 Unprocessable Entity (Validation errors)
     */
    private function _validation_error($errors = [], $message = 'Validation failed')
    {
        log_message('error', '🧪 Validation failed: ' . json_encode($errors));
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(422)
            ->set_output(json_encode([
                'status'  => 'error',
                'message' => $message,
                'errors'  => $errors
            ]));
    }
}
