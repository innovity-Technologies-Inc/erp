<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'third_party/JWT/JWT.php');
require_once(APPPATH . 'third_party/JWT/Key.php');

use Firebase\JWT\ExpiredException;
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
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
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

        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
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
        try {
            // ✅ First-layer Token Auth
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

            log_message('info', 'Headers: ' . json_encode(getallheaders()));

            // ✅ Second-layer Token Check
            $second_token = $this->input->get_request_header('X-Second-Token');
            $view_sensitive_data = false;

            if ($second_token) {
                try {
                    $decoded = JWT::decode($second_token, new Key($this->jwt_key, $this->jwt_algo));
                    if (isset($decoded->customer_id)) {
                        $view_sensitive_data = true;
                        log_message('info', 'Second Layer Token Decoded Successfully.');
                    }
                } catch (Exception $e) {
                    log_message('error', 'Second Layer Token Decode Failed: ' . $e->getMessage());
                }
            } else {
                log_message('error', 'X-Second-Token header not found.');
            }

            // ✅ Pagination Support
            $start = $this->input->get('start');
            $products = $start
                ? $this->Api_model->product_list(15, ($start == 1 ? 0 : $start))
                : $this->Api_model->searchproduct_list();

            // ✅ Load Warehouse Model
            $this->load->model('warehouse/Warehouse_model', 'warehouse_model');

            // ✅ Map Category IDs to Names
            $category_map = [];
            $categories = $this->db->get('product_category')->result();
            foreach ($categories as $cat) {
                $category_map[$cat->category_id] = $cat->category_name;
            }

            // ✅ Process Products
            if (!empty($products)) {
                foreach ($products as $k => $v) {
                    $product_id = $v['product_id'];

                    // Sales and Purchase Quantity
                    $totalSalesQnty = $this->db->select('SUM(quantity) AS totalSalesQnty')
                        ->where('product_id', $product_id)
                        ->get('invoice_details')->row()->totalSalesQnty ?? 0;

                    $totalBuyQnty = $this->db->select('SUM(quantity) AS totalBuyQnty')
                        ->where('product_id', $product_id)
                        ->get('product_purchase_details')->row()->totalBuyQnty ?? 0;

                    $stock_qty = $totalBuyQnty - $totalSalesQnty;

                    // ✅ Image Fix
                    if (!empty($v['image'])) {
                        $products[$k]['image'] = base_url(str_replace('./', '', $v['image']));
                    }

                    // ✅ Add Category Name
                    $products[$k]['category_name'] = $category_map[$v['category_id']] ?? '';

                    // ✅ Sensitive Stock Data
                    if ($view_sensitive_data) {
                        $products[$k]['warehouse_stock_qty'] = $this->warehouse_model->get_warehouse_stock_by_product($product_id);
                    } else {
                        unset($products[$k]['price']);
                        unset($products[$k]['stock_qty']);
                        unset($products[$k]['warehouse_stock_qty']);
                    }

                    // ✅ QR Code Generation
                    $qrImagePath = FCPATH . 'my-assets/image/qr/' . $product_id . '.png';
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
                            'data'     => $product_id,
                            'level'    => 'H',
                            'size'     => 10,
                            'savename' => $qrImagePath
                        ];
                        $this->ciqrcode->generate($params);
                    }

                    // ✅ Attach QR & Barcode
                    $products[$k]['qr_code']  = base_url('my-assets/image/qr/' . $product_id . '.png');
                    $products[$k]['bar_code'] = base_url('Cbarcode/barcode_generator/' . $product_id);

                    // ✅ Product Info By Barcode
                    $product_info = $this->Api_model->product_info_bybarcode($product_id);
                    if (!$view_sensitive_data) {
                        unset($product_info['price']);
                        unset($product_info['stock']);
                    }
                    $products[$k]['product_info_bybarcode'] = $product_info;
                }
            }

            // ✅ Final Response
            $response = !empty($products)
                ? [
                    'status'       => 'success',
                    'message'      => 'Products retrieved successfully.',
                    'product_list' => $products,
                    'total_val'    => $this->db->count_all('product_information')
                ]
                : [
                    'status'       => 'error',
                    'message'      => 'No Product Found',
                    'product_list' => []
                ];

            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(200)
                ->set_output(json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        } catch (Exception $e) {
            return $this->_server_error('Unexpected server error: ' . $e->getMessage());
        }
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
            $customer = $this->get_internal_customer_by_email($email);

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

    public function insert_invoice_payment()
    {
        header('Content-Type: application/json');
        log_message('debug', '🧾 insert_invoice_payment API called');

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
                echo json_encode(['status' => 'token-error', 'message' => 'Second-layer token invalid.']);
                return;
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'token-error', 'message' => 'Second-layer token decode failed: ' . $e->getMessage()]);
            return;
        }

        // ✅ Parse JSON input
        $input = json_decode(file_get_contents("php://input"), true);
        log_message('debug', '📥 Parsed Input: ' . print_r($input, true));

        if (empty($input)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input']);
            return;
        }

        // ✅ Validate required fields
        if (empty($input['transaction_ref'])) {
            echo json_encode(['status' => 'error', 'message' => 'transaction_ref is required']);
            return;
        }

        if (empty($input['detailsinfo']) || !is_array($input['detailsinfo'])) {
            echo json_encode(['status' => 'error', 'message' => 'Missing or invalid product details']);
            return;
        }

        // ✅ Check for duplicate transaction_ref
        $existing = $this->db->get_where('invoice_payment', ['transaction_ref' => $input['transaction_ref']])->row();
        if (!empty($existing)) {
            echo json_encode(['status' => 'error', 'message' => 'Duplicate transaction_ref']);
            return;
        }

        // ✅ Begin transaction
        $this->db->trans_begin();

        try {
            // ✅ Prepare invoice payment data
            $invoice_data = [
                'invoice_date'     => $input['invoice_date'] ?? date('Y-m-d'),
                'createby'         => $input['createby'],
                'customer_id'      => $input['customer_id'],
                'paid_amount'      => $input['paid_amount'],
                'due_amount'       => $input['due_amount'] ?? 0,
                'total_discount'   => $input['total_discount'] ?? 0,
                'total_tax'        => $input['total_tax'] ?? 0,
                'total_amount'     => $input['total_amount'],
                'payment_type'     => $input['payment_type'],
                'payment_ref_doc'  => $input['payment_ref_doc'] ?? '', // optional, no file upload in raw JSON
                'payment_ref'      => $input['payment_ref'] ?? '',
                'transaction_ref'  => $input['transaction_ref'],
                'status'           => $input['status'] ?? 2,
                'created_at'       => date('Y-m-d H:i:s'),
            ];

            // ✅ Insert into invoice_payment table
            $this->db->insert('invoice_payment', $invoice_data);
            $invoice_id = $this->db->insert_id();
            log_message('debug', "✅ Invoice payment inserted with ID: $invoice_id");

            // ✅ Insert products into invoice_payment_details
            foreach ($input['detailsinfo'] as $item) {
                $details_data = [
                    'invoice_id'       => $invoice_id,
                    'product_id'       => $item['product_id'],
                    'product_quantity' => $item['product_quantity'],
                    'product_rate'     => $item['product_rate'],
                    'serial_no'        => $item['serial_no'] ?? '',
                    'created_at'       => date('Y-m-d H:i:s')
                ];
                $this->db->insert('invoice_payment_details', $details_data);
            }

            // ✅ Finalize transaction
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                log_message('error', '❌ Transaction failed, rolling back');
                echo json_encode(['status' => 'error', 'message' => 'Failed to insert invoice']);
                return;
            }

            $this->db->trans_commit();
            echo json_encode([
                'status'     => 'success',
                'invoice_id' => $invoice_id,
                'message'    => 'Invoice payment inserted successfully'
            ]);
        } catch (Exception $e) {
            $this->db->trans_rollback();
            log_message('error', '❌ Exception: ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Something went wrong']);
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
                echo json_encode(['status' => 'token-error', 'message' => 'Second-layer token invalid.']);
                return;
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'token-error', 'message' => 'Second-layer token decode failed: ' . $e->getMessage()]);
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

    public function invoice_generator() 
    {
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

    public function autoapprove($invoice_id)
    {
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
            log_message('info', '🔍 product_search() called.');

            $user = $this->authenticate_token();
            if (!$user) {
                log_message('warning', '❌ Unauthorized access attempt.');
                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(401)
                    ->set_output(json_encode([
                        'response' => [
                            'status'  => 'error',
                            'message' => 'Unauthorized. Please provide a valid access token.'
                        ]
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            }

            log_message('info', '✅ User authenticated.');
            log_message('info', 'Headers: ' . json_encode(getallheaders()));

            $second_token = $this->input->get_request_header('X-Second-Token');
            $view_sensitive_data = false;

            if ($second_token) {
                try {
                    $decoded = JWT::decode($second_token, new Key($this->jwt_key, $this->jwt_algo));
                    if (isset($decoded->customer_id)) {
                        log_message('info', '✅ Second Layer Token Decoded Successfully.');
                        $view_sensitive_data = true;
                    }
                } catch (Exception $e) {
                    log_message('error', '❌ Second Layer Token Decode Failed: ' . $e->getMessage());
                }
            }

            $body = json_decode(trim($this->input->raw_input_stream), true);
            log_message('debug', '📦 Request body: ' . json_encode($body));

            $params = [
                'product_id'   => $body['product_id']   ?? '',
                'product_name' => $body['product_name'] ?? '',
                'category_id'  => $body['category_id']  ?? '',
                'min_price'    => isset($body['min_price']) ? (float)$body['min_price'] : null,
                'max_price'    => isset($body['max_price']) ? (float)$body['max_price'] : null,
                'warehouse_id' => $body['warehouse_id'] ?? null
            ];

            $limit  = isset($body['limit']) && is_numeric($body['limit']) ? (int)$body['limit'] : 10;
            $page   = isset($body['page']) && is_numeric($body['page']) ? (int)$body['page'] : 1;
            $offset = ($page - 1) * $limit;

            log_message('debug', '📄 Params for DB query: ' . json_encode($params));
            log_message('debug', "📄 Pagination - Limit: $limit, Page: $page, Offset: $offset");

            $total_count = $this->countProductSearchResults($params);
            $products    = $this->getProductSearchResults($params, $limit, $offset);

            log_message('info', '📦 Total products fetched: ' . count($products));

            $this->load->model('warehouse/Warehouse_model', 'warehouse_model');

            $category_map = [];
            $categories = $this->db->get('product_category')->result();
            foreach ($categories as $cat) {
                $category_map[$cat->category_id] = $cat->category_name;
            }

            foreach ($products as $k => $v) {
                $product_id = $v['product_id'];

                $totalSalesQnty = (float)($this->db->select('SUM(quantity) AS totalSalesQnty')
                    ->where('product_id', $product_id)
                    ->get('invoice_details')->row()->totalSalesQnty ?? 0);

                $totalBuyQnty = (float)($this->db->select('SUM(quantity) AS totalBuyQnty')
                    ->where('product_id', $product_id)
                    ->get('product_purchase_details')->row()->totalBuyQnty ?? 0);

                $stock_qty = $totalBuyQnty - $totalSalesQnty;

                // Image
                $products[$k]['image'] = !empty($v['image']) ? base_url(str_replace('./', '', $v['image'])) : '';

                // Category Name
                $products[$k]['category_name'] = $category_map[$v['category_id']] ?? '';

                // Stock Details
                if ($view_sensitive_data) {
                    $products[$k]['stock_qty'] = $stock_qty;
                    $products[$k]['warehouse_stock_qty'] = $this->warehouse_model->get_warehouse_stock_by_product($product_id, $params['warehouse_id']);
                } else {
                    unset($products[$k]['price']);
                    unset($products[$k]['stock_qty']);
                    unset($products[$k]['warehouse_stock_qty']);
                }

                // QR Code
                $qrImagePath = FCPATH . 'my-assets/image/qr/' . $product_id . '.png';
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
                    $this->ciqrcode->generate([
                        'data'     => $product_id,
                        'level'    => 'H',
                        'size'     => 10,
                        'savename' => $qrImagePath
                    ]);
                    log_message('info', "🧾 QR generated for product_id: $product_id");
                }

                $products[$k]['qr_code']  = base_url('my-assets/image/qr/' . $product_id . '.png');
                $products[$k]['bar_code'] = base_url('Cbarcode/barcode_generator/' . $product_id);

                $product_info = $this->Api_model->product_info_bybarcode($product_id);
                if (!$view_sensitive_data) {
                    unset($product_info['price']);
                    unset($product_info['stock']);
                }
                $products[$k]['product_info_bybarcode'] = $product_info;
            }

            $response = !empty($products)
                ? [
                    'status'        => 'ok',
                    'product_list'  => $products,
                    'total_val'     => $total_count,
                    'page'          => $page,
                    'page_count'    => ceil($total_count / $limit)
                ]
                : [
                    'status'        => 'error',
                    'product_list'  => [],
                    'total_val'     => 0,
                    'page'          => $page,
                    'page_count'    => 0,
                    'message'       => 'No Product Found'
                ];

            log_message('debug', '🟢 Final API Response: ' . json_encode(['response' => $response]));
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['response' => $response], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        } catch (Exception $e) {
            log_message('error', '❌ Exception in product_search(): ' . $e->getMessage());
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'response' => [
                        'status'  => 'error',
                        'message' => 'Server error: ' . $e->getMessage()
                    ]
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        }
    }

    // ✅ Builds search query with reusable conditions
    public function buildProductSearchQuery(array $params)
    {
        $this->db->start_cache();
        $this->db->from('product_information');

        if (!empty($params['product_id'])) {
            $this->db->where('product_information.product_id', $params['product_id']);
        }

        if (!empty($params['product_name'])) {
            $this->db->like('product_information.product_name', $params['product_name']);
        }

        if (!empty($params['category_id'])) {
            $category_ids = $this->get_all_related_category_ids($params['category_id']);
            $this->db->where_in('product_information.category_id', $category_ids);
        }

        if (isset($params['min_price']) && is_numeric($params['min_price'])) {
            $this->db->where('CAST(product_information.price AS DECIMAL(10,2)) >=', (float)$params['min_price']);
        }

        if (isset($params['max_price']) && is_numeric($params['max_price'])) {
            $this->db->where('CAST(product_information.price AS DECIMAL(10,2)) <=', (float)$params['max_price']);
        }

        $this->db->stop_cache();
        return $this->db;
    }

    // Helper - ✅ Fetches paginated product search results
    public function getProductSearchResults(array $params, int $limit, int $offset)
    {
        log_message('info', '🔍 getProductSearchResults called with params: ' . json_encode($params));
        log_message('debug', 'Applying base SELECT and JOIN product_category');

        // ✅ FIX: Correct use of DISTINCT
        $this->db->distinct();
        $this->db->select('p.product_id, p.id, p.category_id, p.product_name, p.price, p.unit, p.tax, p.serial_no, p.product_vat, p.product_model, p.product_details, p.image, p.status, c.category_name');
        $this->db->from('product_information p');
        $this->db->join('product_category c', 'c.category_id = p.category_id', 'left');

        if (!empty($params['warehouse_id'])) {
            log_message('debug', 'Joining batch_master on warehouse_id: ' . $params['warehouse_id']);
            $this->db->join('batch_master bm', 'bm.product_id = p.product_id', 'inner');
            $this->db->where('bm.warehouse_id', $params['warehouse_id']);
            $this->db->where('bm.available_quantity >', 0);
        }

        if (!empty($params['product_id'])) {
            log_message('debug', 'Filtering by product_id: ' . $params['product_id']);
            $this->db->where('p.product_id', $params['product_id']);
        }

        if (!empty($params['product_name'])) {
            log_message('debug', 'Searching product_name like: ' . $params['product_name']);
            $this->db->like('p.product_name', $params['product_name']);
        }

        if (!empty($params['category_id'])) {
            log_message('debug', 'Filtering by category_id: ' . $params['category_id']);
            $this->db->where('p.category_id', $params['category_id']);
        }

        if (!is_null($params['min_price'])) {
            log_message('debug', 'Filtering by min_price: ' . $params['min_price']);
            $this->db->where('p.price >=', $params['min_price']);
        }

        if (!is_null($params['max_price'])) {
            log_message('debug', 'Filtering by max_price: ' . $params['max_price']);
            $this->db->where('p.price <=', $params['max_price']);
        }

        log_message('debug', 'Applying order by p.id DESC');
        $this->db->order_by('p.id', 'DESC');

        log_message('debug', 'Applying pagination: limit=' . $limit . ', offset=' . $offset);
        $this->db->limit($limit, $offset);

        $query = $this->db->get();
        log_message('debug', 'Final SQL Query: ' . $this->db->last_query());

        $results = $query->result_array();
        log_message('info', 'Total products fetched: ' . count($results));

        return $results;
    }

    // Helper - ✅ Counts total product results matching filters
    public function countProductSearchResults(array $params)
    {
        $this->db->from('product_information p');

        // Join product_category only if needed
        if (!empty($params['warehouse_id'])) {
            $this->db->join('batch_master bm', 'bm.product_id = p.product_id', 'inner');
            $this->db->where('bm.warehouse_id', $params['warehouse_id']);
            $this->db->where('bm.available_quantity >', 0);
        }

        if (!empty($params['product_id'])) {
            $this->db->where('p.product_id', $params['product_id']);
        }

        if (!empty($params['product_name'])) {
            $this->db->like('p.product_name', $params['product_name']);
        }

        if (!empty($params['category_id'])) {
            $this->db->where('p.category_id', $params['category_id']);
        }

        if (!is_null($params['min_price'])) {
            $this->db->where('p.price >=', $params['min_price']);
        }

        if (!is_null($params['max_price'])) {
            $this->db->where('p.price <=', $params['max_price']);
        }

        return $this->db->count_all_results();
    }

    // Helper - ✅ Fetches all sub-category IDs for a given parent category ID
    private function get_all_related_category_ids($category_id)
    {
        $category_id = (int)$category_id;
        if ($category_id <= 0) {
            return [];
        }

        $this->db->select('category_id, parent_id')
                ->from('product_category')
                ->where('status', 1);

        $query = $this->db->get();
        if (!$query) {
            log_message('error', 'Failed to fetch categories: ' . $this->db->error()['message']);
            return [$category_id];
        }

        $all_categories = $query->result_array();
        $children_map = [];

        foreach ($all_categories as $cat) {
            $parent_id = (int)$cat['parent_id'];
            if ($parent_id > 0) {
                $children_map[$parent_id][] = (int)$cat['category_id'];
            }
        }

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

    public function second_layer_logout()
    {
        try {
            log_message('debug', '[SecondLayerLogout] Initiated logout process');

            // ✅ First-layer Bearer token authentication
            $user = $this->authenticate_token();
            if (!$user) {
                return $this->_unauthorized('Unauthorized. Bearer token required for 2nd layer logout.');
            }

            // ✅ Read second-layer JWT token from header
            $second_token = $this->input->get_request_header('X-Second-Token');
            if (empty($second_token)) {
                log_message('error', '[SecondLayerLogout] Missing second-layer token');
                return $this->_unauthorized('Second-layer token required.');
            }

            // ✅ Decode second-layer JWT token
            try {
                $decoded = JWT::decode($second_token, new Key($this->jwt_key, $this->jwt_algo));
                $customer_id = $decoded->customer_id ?? null;
                $username = $decoded->username ?? null;

                if (!$customer_id || !$username) {
                    log_message('error', '[SecondLayerLogout] Token missing required fields.');
                    return $this->_unauthorized('Invalid second-layer token.');
                }
            } catch (Exception $e) {
                log_message('error', '[SecondLayerLogout] Token decode failed: ' . $e->getMessage());
                return $this->_unauthorized('Invalid or expired second-layer token.');
            }

            log_message('debug', "[SecondLayerLogout] Logging out customer_id: {$customer_id}, username: {$username}");

            // ✅ Clear FCM token from customer_auth
            $this->db->where('customer_id', $customer_id)
                    ->where('username', $username)
                    ->update('customer_auth', [
                        'fcm_token'  => null,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);

            if ($this->db->affected_rows() > 0) {
                log_message('debug', "[SecondLayerLogout] FCM token cleared for customer_id: {$customer_id}");
            } else {
                log_message('warning', "[SecondLayerLogout] No rows affected. Possibly already logged out.");
            }

            return $this->_success(null, '2nd Layer Logout Successful.');

        } catch (Exception $e) {
            log_message('error', '[SecondLayerLogout] Error: ' . $e->getMessage());
            return $this->_server_error('Something went wrong during 2nd layer logout.');
        }
    }

    public function get_profile()
    {
        try {
            log_message('debug', '[GetProfile] Initiated profile fetch');

            // 🔐 Step 1: First-layer token authentication
            $user = $this->authenticate_token();
            if (!$user) {
                return $this->_unauthorized('Unauthorized. Please provide a valid access token.');
            }

            // 🔐 Step 2: Validate and decode second-layer token
            $second_token = $this->input->get_request_header('X-Second-Token');
            if (empty($second_token)) {
                log_message('error', '🔒 Missing second-layer token');
                return $this->_unauthorized('Secondary authentication required.');
            }

            try {
                $decoded = JWT::decode($second_token, new Key($this->jwt_key, $this->jwt_algo));
                log_message('debug', '🔍 Decoded second-layer token: ' . json_encode($decoded));

                if (empty($decoded->customer_id)) {
                    log_message('error', '🔒 customer_id missing in second-layer token');
                    return $this->_unauthorized('Invalid second-layer token.');
                }

                $customer_id = $decoded->customer_id;
                log_message('debug', "[GetProfile] Verified customer_id from second-layer token: {$customer_id}");

            } catch (Exception $e) {
                log_message('error', '🔒 Failed to decode second-layer token: ' . $e->getMessage());
                return $this->_unauthorized('Invalid or expired second-layer token.');
            }

            // ✅ Fetch customer_information
            $customer = $this->db->get_where('customer_information', ['customer_id' => $customer_id])->row_array();
            if (!$customer) {
                return $this->_not_found('Customer profile not found.');
            }

            // 🔁 Update sales_permit with full URL if available
            if (!empty($customer['sales_permit'])) {
                $customer['sales_permit'] = base_url(str_replace('./', '', 'uploads/sales_permits/' . $customer['sales_permit']));
            }

            // ✅ Fetch auth_status only
            $auth_row = $this->db->select('status')->get_where('customer_auth', ['customer_id' => $customer_id])->row_array();
            $auth = ['auth_status' => $auth_row['status'] ?? null];

            // ✅ Fetch commission_type and commision_value only
            $commission_row = $this->db->select('commision_value, comission_type')
                ->order_by('id', 'DESC')
                ->get_where('customer_comission', ['customer_id' => $customer_id])
                ->row_array();

            $commission = [
                'comission_type'  => $commission_row['comission_type'] ?? null,
                'commision_value' => $commission_row['commision_value'] ?? null
            ];

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

    public function profile_update()
    {
        try {
            log_message('debug', '[ProfileUpdate] Initiated');

            // 🔐 First-layer token check
            $user = $this->authenticate_token();
            if (!$user) {
                return $this->_unauthorized('Unauthorized. Please provide a valid access token.');
            }

            // 🔐 Second-layer token check
            $second_token = $this->input->get_request_header('X-Second-Token');
            if (empty($second_token)) {
                return $this->_unauthorized('Secondary authentication required.');
            }

            try {
                $decoded = JWT::decode($second_token, new Key($this->jwt_key, $this->jwt_algo));
                if (empty($decoded->customer_id)) {
                    return $this->_unauthorized('Invalid second-layer token.');
                }
                $customer_id = $decoded->customer_id;
                log_message('debug', "[ProfileUpdate] Authenticated customer_id: $customer_id");
            } catch (Exception $e) {
                return $this->_unauthorized('Invalid or expired second-layer token.');
            }

            // ✅ Optional file upload for sales_permit
            $sales_permit = '';
            if (!empty($_FILES['sales_permit']['name'])) {
                $sales_permit = $this->_upload_file('sales_permit', './uploads/sales_permits/');
                if ($sales_permit === false) return;
            }

            // ✅ Update data preparation
            $update_data = [
                'customer_name'        => $this->input->post('customer_name'),
                'customer_address'     => $this->input->post('address'),
                'address2'             => $this->input->post('address2'),
                'email_address'        => $this->input->post('email_address'),
                'contact'              => $this->input->post('contact'),
                'phone'                => $this->input->post('phone'),
                'fax'                  => $this->input->post('fax'),
                'city'                 => $this->input->post('city'),
                'state'                => $this->input->post('state'),
                'zip'                  => $this->input->post('zip'),
                'country'              => $this->input->post('country'),
                'sales_permit_number'  => $this->input->post('sales_permit_number')
            ];

            // Only update sales permit if a new file is uploaded
            if (!empty($sales_permit)) {
                $update_data['sales_permit'] = $sales_permit;
            }

            // ✅ Perform update
            $this->db->where('customer_id', $customer_id)->update('customer_information', $update_data);

            if ($this->db->affected_rows() > 0) {
                return $this->_success(null, 'Profile updated successfully.');
            } else {
                return $this->_success(null, 'No changes made to profile.');
            }

        } catch (Exception $e) {
            log_message('error', '[ProfileUpdate] Error: ' . $e->getMessage());
            return $this->_server_error('Failed to update profile.');
        }
    }

    public function insert_cart()
    {
        header('Content-Type: application/json');
        log_message('debug', '🛒 API insert_cart called');

        // ✅ Log headers
        $headers = getallheaders();
        log_message('debug', '📋 Request headers: ' . print_r($headers, true));

        // ✅ First-layer authentication
        $user = $this->authenticate_token();
        if (!$user) {
            log_message('error', '❌ First-layer token invalid');
            return $this->_unauthorized('Unauthorized. Bearer token missing or invalid.', 'unauthorized_token');
        }
        log_message('debug', '✅ First-layer token verified. User: ' . print_r($user, true));

        // ✅ Second-layer token
        $second_token = $this->input->get_request_header('X-Second-Token');
        if (!$second_token) {
            log_message('error', '❌ Missing X-Second-Token header');
            return $this->_forbidden('Missing second-layer token.', 'missing_second_layer_token');
        }

        try {
            $decoded = JWT::decode($second_token, new Key($this->jwt_key, $this->jwt_algo));
            log_message('debug', '🔓 Decoded second-layer token: ' . print_r($decoded, true));

            if (!isset($decoded->customer_id)) {
                log_message('error', '❌ customer_id not found in second-layer token');
                return $this->_unauthorized('Invalid or expired second-layer token.', 'invalid_second_layer_token');
            }
        } catch (Exception $e) {
            log_message('error', '❌ Second-layer token decode failed: ' . $e->getMessage());
            return $this->_server_error_exception($e, 'second_layer_token_decode_failed', 'Second-layer token decode failed');
        }

        $customer_id = $decoded->customer_id;
        log_message('debug', "🧑 customer_id from second-layer token: $customer_id");

        // ✅ Parse JSON input
        $raw_input = file_get_contents("php://input");
        $input = json_decode($raw_input, true);
        log_message('debug', '📦 Raw JSON input: ' . $raw_input);
        log_message('debug', '🧾 Parsed input: ' . print_r($input, true));

        if (empty($input) || !isset($input['cart_items']) || !is_array($input['cart_items'])) {
            log_message('error', '❌ Invalid or missing cart_items array');
            return $this->_bad_request('Invalid or missing cart_items array.', 'invalid_cart_items');
        }

        $success_count = 0;
        $failed_items  = [];

        foreach ($input['cart_items'] as $item) {
            log_message('debug', '📥 Processing cart item: ' . print_r($item, true));

            $missing_fields = [];

            if (!isset($item['product_id']))   $missing_fields[] = 'product_id';
            if (!isset($item['batch_id']))     $missing_fields[] = 'batch_id';
            if (!isset($item['warehouse_id'])) $missing_fields[] = 'warehouse_id';
            if (!isset($item['quantity']))     $missing_fields[] = 'quantity';

            if (!empty($missing_fields)) {
                log_message('error', '⚠️ Missing fields in cart_items: ' . implode(', ', $missing_fields));
                $failed_items[] = [
                    'item' => $item,
                    'reason' => 'Missing required fields: ' . implode(', ', $missing_fields)
                ];
                continue;
            }

            $product_id   = $item['product_id'];
            $batch_id     = $item['batch_id'];
            $warehouse_id = $item['warehouse_id'];
            $quantity     = $item['quantity'];

            // ✅ Ensure batch exists
            $batch_exists = $this->db->select('1')
                ->from('batch_master')
                ->where('batch_id', $batch_id)
                ->where('product_id', $product_id)
                ->where('warehouse_id', $warehouse_id)
                ->limit(1)
                ->get()
                ->row();

            if (!$batch_exists) {
                log_message('error', "❌ Batch not found: batch_id=$batch_id, product_id=$product_id, warehouse_id=$warehouse_id");
                $failed_items[] = [
                    'item' => $item,
                    'reason' => 'Batch not found for product and warehouse.'
                ];
                continue;
            }

            // ✅ Get product price
            $product = $this->db->select('price')
                ->from('product_information')
                ->where('product_id', $product_id)
                ->get()
                ->row();

            if (!$product) {
                log_message('error', "❌ Product not found: $product_id");
                $failed_items[] = [
                    'item' => $item,
                    'reason' => 'Product not found.'
                ];
                continue;
            }

            $price = (float) $product->price;

            // ✅ Get commission (discount)
            $discount_row = $this->db->select('commision_value')
                ->from('customer_comission')
                ->where('customer_id', $customer_id)
                ->get()
                ->row();

            $discount = $discount_row ? (float) $discount_row->commision_value : 0.00;

            log_message('debug', '💸 Final discount data: ' . print_r($discount_row, true));
            $data = [
                'customer_id'  => $customer_id,
                'product_id'   => $product_id,
                'batch_id'     => $batch_id,
                'warehouse_id' => $warehouse_id,
                'quantity'     => (int)$quantity,
                'price'        => $price,
                'discount'     => $discount,
                'note'         => null,
                'status'       => 'pending',
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s')
            ];

            log_message('debug', '📋 Final cart insert data: ' . json_encode($data));

            try {
                $this->db->insert('cart', $data);
                $success_count++;
                log_message('debug', '✅ Cart item inserted successfully');
            } catch (Exception $e) {
                log_message('error', '❌ DB Insert failed: ' . $e->getMessage());
                $failed_items[] = [
                    'item' => $item,
                    'reason' => 'Database insert failed: ' . $e->getMessage()
                ];
            }
        }

        if ($success_count === 0) {
            log_message('error', '❌ No cart items inserted. All failed.');
            return $this->_server_error('No cart items were inserted. Check logs for details.', 'insert_cart_failed');
        }

        log_message('debug', "✅ Cart insertion completed. Success: $success_count | Failed: " . count($failed_items));
        return $this->_success([
            'inserted_count' => $success_count,
            'failed_items'   => $failed_items
        ], 'Cart items inserted successfully.');
    }


    public function cart_list()
    {
        header('Content-Type: application/json');
        log_message('debug', '🛒 API cart_list called');

        // ✅ First-layer authentication
        $user = $this->authenticate_token();
        if (!$user) {
            echo json_encode([
                'status' => 'token-error',
                'message' => 'Unauthorized. Bearer token missing or invalid.'
            ]);
            return;
        }

        // ✅ Second-layer authentication
        $second_token = $this->input->get_request_header('X-Second-Token');
        if (!$second_token) {
            echo json_encode([
                'status' => '2nd-token-error',
                'message' => 'Missing second-layer token.'
            ]);
            return;
        }

        try {
            $decoded = JWT::decode($second_token, new Key($this->jwt_key, $this->jwt_algo));
            if (!isset($decoded->customer_id)) {
                echo json_encode([
                    'status' => 'token-error',
                    'message' => 'Second-layer token invalid.'
                ]);
                return;
            }
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'token-error',
                'message' => 'Second-layer token decode failed: ' . $e->getMessage()
            ]);
            return;
        }

        $customer_id = $decoded->customer_id;

        // ✅ Fetch cart items with image
        $cart_items = $this->db->select('
                cart.id as cart_id,
                cart.product_id,
                cart.batch_id,
                cart.warehouse_id,
                cart.quantity,
                cart.price,
                cart.discount,
                cart.note,
                cart.status,
                cart.created_at,
                cart.updated_at,
                product_information.product_name,
                product_information.image'
            )
            ->from('cart')
            ->join('product_information', 'product_information.product_id = cart.product_id', 'left')
            ->where('cart.customer_id', $customer_id)
            ->where('cart.status', 'pending')
            ->get()
            ->result_array();

        // ✅ Add image URL formatting
        foreach ($cart_items as &$item) {
            if (!empty($item['image'])) {
                $item['image'] = base_url(str_replace('./', '', $item['image']));
            } else {
                $item['image'] = null;
            }
        }

        // ✅ Respond
        echo json_encode([
            'status'      => 'success',
            'message'     => 'Cart items retrieved successfully.',
            'cart_items'  => $cart_items
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    public function remove_cart_item_by_id()
    {
        header('Content-Type: application/json');
        log_message('debug', '🗑️ API remove_cart_item_by_id called');

        // ✅ First-layer authentication
        $user = $this->authenticate_token();
        if (!$user) {
            echo json_encode(['status' => 'unauthorized', 'message' => 'Unauthorized. Bearer token missing or invalid.']);
            return;
        }

        // ✅ Second-layer authentication
        $second_token = $this->input->get_request_header('X-Second-Token');
        if (!$second_token) {
            echo json_encode(['status' => 'token-error', 'message' => 'Missing second-layer token.']);
            return;
        }

        try {
            $decoded = JWT::decode($second_token, new Key($this->jwt_key, $this->jwt_algo));
            if (!isset($decoded->customer_id)) {
                echo json_encode(['status' => 'token-error', 'message' => 'Second-layer token invalid.']);
                return;
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'token-error', 'message' => 'Second-layer token decode failed: ' . $e->getMessage()]);
            return;
        }

        $customer_id = $decoded->customer_id;

        // ✅ Parse input
        $input = json_decode(file_get_contents("php://input"), true);
        log_message('debug', '🧾 Raw remove_cart_item_by_id input: ' . print_r($input, true));

        if (empty($input['cart_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Missing required parameter: cart_id.']);
            return;
        }

        // ✅ DELETE using cart_id and customer_id for safety
        $this->db->where('id', $input['cart_id'])
                ->where('customer_id', $customer_id)
                ->delete('cart');

        if ($this->db->affected_rows() > 0) {
            echo json_encode([
                'status'  => 'success',
                'message' => 'Cart item removed successfully using cart_id.'
            ]);
        } else {
            echo json_encode([
                'status'  => 'info',
                'message' => 'No matching cart item found with the provided cart_id.'
            ]);
        }
    }


    public function cart_update()
    {
        header('Content-Type: application/json');
        log_message('debug', '🛒 API cart_update called');

        // ✅ First-layer authentication
        $user = $this->authenticate_token();
        if (!$user) {
            return $this->_unauthorized('Unauthorized. Bearer token missing or invalid.', 'unauthorized_token');
        }

        // ✅ Second-layer authentication
        $second_token = $this->input->get_request_header('X-Second-Token');
        if (!$second_token) {
            return $this->_forbidden('Missing second-layer token.', 'missing_second_layer_token');
        }

        try {
            $decoded = JWT::decode($second_token, new Key($this->jwt_key, $this->jwt_algo));
            if (!isset($decoded->customer_id)) {
                return $this->_unauthorized('Invalid or expired second-layer token.', 'invalid_second_layer_token');
            }
        } catch (Exception $e) {
            return $this->_server_error('Second-layer token decode failed: ' . $e->getMessage(), 'second_layer_token_decode_failed');
        }

        $customer_id = $decoded->customer_id;

        // ✅ Parse incoming JSON
        $input = json_decode(trim(file_get_contents('php://input')), true);

        if (!isset($input['cart_id']) || !is_numeric($input['cart_id'])) {
            return $this->_bad_request('Invalid or missing cart_id.', 'invalid_cart_id');
        }

        $cart_id = $input['cart_id'];

        // ✅ Fetch the cart record and validate customer ownership
        $cart = $this->db->get_where('cart', ['id' => $cart_id, 'customer_id' => $customer_id])->row();
        if (!$cart) {
            return $this->_not_found('Cart item not found or does not belong to customer.', 'cart_not_found');
        }

        // ✅ Prepare update data
        $update_data = [];

        if (isset($input['quantity']) && is_numeric($input['quantity'])) {
            $update_data['quantity'] = (int)$input['quantity'];
        }
        if (isset($input['batch_id'])) {
            $update_data['batch_id'] = $input['batch_id'];
        }
        if (isset($input['warehouse_id'])) {
            $update_data['warehouse_id'] = $input['warehouse_id'];
        }
        if (isset($input['note'])) {
            $update_data['note'] = $input['note'];
        }
        if (isset($input['price']) && is_numeric($input['price'])) {
            $update_data['price'] = (float)$input['price'];
        }
        if (isset($input['discount']) && is_numeric($input['discount'])) {
            $update_data['discount'] = (float)$input['discount'];
        }

        if (empty($update_data)) {
            return $this->_bad_request('No valid fields provided for update.', 'no_update_fields');
        }

        $update_data['updated_at'] = date('Y-m-d H:i:s');

        // ✅ Update the cart item
        try {
            $this->db->where('id', $cart_id)->update('cart', $update_data);
        } catch (Exception $e) {
            return $this->_server_error('Failed to update cart item: ' . $e->getMessage(), 'cart_update_failed');
        }

        return $this->_success([
            'cart_id'        => $cart_id,
            'updated_fields' => $update_data
        ], 'Cart item updated successfully.');
    }


    public function check_out()
    {
        log_message('debug', '🛒 API check_out called');

        $user = $this->authenticate_token();
        if (!$user) {
            return $this->_unauthorized('Unauthorized. Bearer token missing or invalid.');
        }

        $second_token = $this->input->get_request_header('X-Second-Token');
        if (!$second_token) {
            return $this->_unauthorized('Missing second-layer token (X-Second-Token).');
        }

        try {
            $decoded = JWT::decode($second_token, new Key($this->jwt_key, $this->jwt_algo));
            log_message('debug', '📦 Decoded second-layer JWT: ' . print_r($decoded, true));

            if (!isset($decoded->customer_id)) {
                log_message('error', '❌ customer_id missing in second-layer token');
                return $this->_unauthorized('Second-layer token invalid.');
            }

            $customer_id = $decoded->customer_id;
            log_message('debug', "✅ Extracted customer_id from token: $customer_id");
        } catch (Exception $e) {
            log_message('error', '❌ JWT decode error: ' . $e->getMessage());
            return $this->_unauthorized('Second-layer token decode failed: ' . $e->getMessage());
        }

        $input = $this->input->post();
        $cart_ids_raw  = $input['cart_id'] ?? null;
        $paid_amount   = floatval($input['paid_amount'] ?? 0);
        $delivery_note = $input['delivery_note'] ?? '';
        $payment_type  = intval($input['payment_type'] ?? 1);
        $payment_ref   = $input['payment_ref'] ?? '';

        $payment_ref_doc = '';
        if (!empty($_FILES['payment_ref_doc']['name'])) {
            $upload_dir = './uploads/payment_refs/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $this->load->library('upload');

            $ext = pathinfo($_FILES['payment_ref_doc']['name'], PATHINFO_EXTENSION);
            $unique_name = 'file_upload_' . time() . '_' . uniqid() . '.' . $ext;

            // Override $_FILES entry with the new unique file name
            $_FILES['payment_ref_doc']['name'] = $unique_name;

            $config['upload_path']   = $upload_dir;
            $config['allowed_types'] = 'pdf|jpg|jpeg|png';
            $config['file_name']     = $unique_name;
            $config['max_size']      = 2048;

            $this->upload->initialize($config);

            if ($this->upload->do_upload('payment_ref_doc')) {
                $payment_ref_doc = 'uploads/payment_refs/' . $unique_name;
            } else {
                log_message('error', '❌ Upload error: ' . $this->upload->display_errors());
                return $this->_bad_request('File upload failed: ' . strip_tags($this->upload->display_errors()));
            }
        }

        if (empty($cart_ids_raw)) {
            log_message('error', '❌ Missing required parameter: cart_id');
            return $this->_bad_request('Missing required parameter: cart_id');
        }

        $cart_ids = array_filter(array_map('intval', explode(',', $cart_ids_raw)));
        if (empty($cart_ids)) {
            log_message('error', '❌ Invalid cart_id values: ' . $cart_ids_raw);
            return $this->_bad_request('Invalid cart_id values');
        }

        $this->db->where_in('id', $cart_ids);
        $this->db->where('status', 'pending');
        $this->db->where('customer_id', $customer_id);
        $carts = $this->db->get('cart')->result_array();

        if (empty($carts)) {
            return $this->_not_found('No pending carts found for given IDs and customer.');
        }

        $detailsinfo = [];
        $total_discount = 0;
        $total = 0;

        foreach ($carts as $cart) {
            $qty = (float) $cart['quantity'];
            $rate = (float) $cart['price'];
            $discount_percent = (float) $cart['discount'];
            $line_total = $qty * $rate;
            $line_discount = ($discount_percent / 100) * $line_total;

            $detailsinfo[] = [
                'product_id'       => $cart['product_id'],
                'product_quantity' => $qty,
                'product_rate'     => $rate,
                'discount'         => $discount_percent,
                'serial_no'        => ''
            ];

            $total += $line_total;
            $total_discount += $line_discount;
        }

        $due_amount = max(0, $total - $paid_amount - $total_discount);

        $payload = [
            'customer_id'     => $customer_id,
            'createby'        => $customer_id,
            'paid_amount'     => $paid_amount,
            'due_amount'      => $due_amount,
            'total_discount'  => $total_discount,
            'total_tax'       => 0,
            'total_amount'    => 0,
            'invoice_date'    => date('Y-m-d'),
            'inva_details'    => 'Cart Checkout (Multiple)',
            'payment_type'    => $payment_type,
            'payment_ref'     => $payment_ref,
            'payment_ref_doc' => $payment_ref_doc,
            'invoice_by'      => 2,
            'delivery_note'   => $delivery_note,
            'status'          => 2,
            'transaction_ref' => 'TXN-' . time(),
            'detailsinfo'     => $detailsinfo
        ];

        $response = $this->internal_insert_invoice_payment($payload);
        log_message('debug', '🧾 Invoice payment response: ' . json_encode($response));

        $this->db->where_in('id', $cart_ids)->update('cart', ['status' => 'completed']);

        return $this->_success($response, 'Invoice created successfully via check_out');
    }


    public function invoice_list()
    {
        try {
            log_message('debug', 'API Request: invoice_list');

            // ✅ Step 1: First-layer Auth
            $user = $this->authenticate_token();
            if (!$user) {
                return $this->_unauthorized('Unauthorized. Please provide a valid access token.');
            }

            // ✅ Step 2: Second-layer Token
            $second_token = $this->input->get_request_header('X-Second-Token');
            if (!$second_token) {
                return $this->_unauthorized('Missing required second-layer authentication token (X-Second-Token).');
            }

            try {
                    $decoded = JWT::decode($second_token, new Key($this->jwt_key, $this->jwt_algo));
                    if (empty($decoded->customer_id)) {
                        return $this->_unauthorized('Second-layer token is invalid or expired.');
                    }
                    $customer_id = $decoded->customer_id;
                } catch (Exception $e) {
                    return $this->_unauthorized('Second-layer token decode failed: ' . $e->getMessage());
                }

            // ✅ Step 3: Load Model and Get Data
            $this->load->model('invoice/Invoice_model', 'invoice_model');

            $postData = $this->input->post();
            $postData['customer_id'] = $customer_id;

            $invoices = $this->invoice_model->get_customer_invoice_list($postData);

            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(200)
                ->set_output(json_encode([
                    'status'  => 'success',
                    'message' => 'Invoices retrieved successfully.',
                    'data'    => $invoices
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        } catch (Exception $e) {
            return $this->_server_error('Unexpected server error: ' . $e->getMessage());
        }
    }


    // This function retrieves the details of a specific invoice.
    // It requires a valid access token and a second-layer token for authentication.

    public function invoice_details()
    {
        try {
            log_message('debug', 'API Request: invoice_details');

            // ✅ Step 1: First-layer Token
            $user = $this->authenticate_token();
            if (!$user) {
                return $this->_unauthorized('Unauthorized. Please provide a valid access token.');
            }

            // ✅ Step 2: Second-layer Token
            $second_token = $this->input->get_request_header('X-Second-Token');
            if (!$second_token) {
                return $this->_unauthorized('Missing required second-layer authentication token (X-Second-Token).');
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

            // ✅ Step 3: Validate Input
            $invoice_id = $this->input->get('invoice_id', TRUE);

            if (!$invoice_id) {
                $invoice_id = $this->input->post('invoice_id', TRUE);
            }

            if (!$invoice_id) {
                $json = json_decode(file_get_contents('php://input'), true);
                $invoice_id = $json['invoice_id'] ?? null;
            }

            if (empty($invoice_id)) {
                return $this->_bad_request('Missing required parameter: invoice_id');
            }

            // ✅ Step 4: Fetch Invoice Record
            $invoice = $this->db->select('id, customer_id')
                ->from('invoice')
                ->where('invoice_id', $invoice_id)
                ->get()
                ->row();

            if (!$invoice) {
                return $this->_bad_request("Invoice not found for invoice_id: $invoice_id");
            }

            // ✅ Step 5: Validate ownership
            if ($invoice->customer_id != $customer_id) {
                return $this->_unauthorized("You do not have access to this invoice.");
            }

            // ✅ Step 6: Fetch invoice details
            $this->load->model('invoice/Invoice_model', 'invoice_model');
            $details = $this->invoice_model->invoice_details_by_invoice_id($invoice->id);

            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(200)
                ->set_output(json_encode([
                    'status'     => 'success',
                    'message'    => 'Invoice details retrieved successfully.',
                    'invoice_id' => $invoice_id,
                    'details'    => $details
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        } catch (Exception $e) {
            return $this->_server_error('Unexpected server error: ' . $e->getMessage());
        }
    }

    // Web Contact Form Submission
    // This function allows users to submit contact messages through a web form.
    // It requires a valid access token for authentication.
    // ✅ 200 - OK

    public function insert_contact()
    {
        log_message('debug', '🔐 Checking authentication for insert_contact...');
        $user = $this->authenticate_token();
        if (!$user) {
            log_message('error', '❌ Unauthorized access attempt to insert_contact');
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(401)
                ->set_output(json_encode([
                    'status'  => 'token-error',
                    'message' => 'Unauthorized. Please provide a valid access token.'
                ]));
        }

        $input = json_decode(trim(file_get_contents('php://input')), true);

        $name    = isset($input['name']) ? trim($input['name']) : '';
        $email   = isset($input['email']) ? trim($input['email']) : '';
        $phone   = isset($input['phone']) ? trim($input['phone']) : null;
        $subject = isset($input['subject']) ? trim($input['subject']) : null;
        $message = isset($input['message']) ? trim($input['message']) : '';

        log_message('debug', "[insert_contact] Incoming Data: Name: $name, Email: $email, Subject: $subject");

        // Basic validation
        if (empty($name) || empty($email) || empty($message)) {
            return $this->_bad_request('Name, email, and message are required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->_bad_request('Invalid email format.');
        }

        // Insert into contacts table
        $data = [
            'name'       => $name,
            'email'      => $email,
            'phone'      => $phone,
            'subject'    => $subject,
            'message'    => $message,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($this->db->insert('contacts', $data)) {
            $contact_id = $this->db->insert_id();

            // Optionally: Notify admin by email (if needed)
            /*
            $this->load->library('sendmail_lib');
            $admin_email = 'support@example.com';
            $this->sendmail_lib->send(
                $admin_email,
                'New Contact Form Submission',
                "<p><strong>Name:</strong> $name</p><p><strong>Email:</strong> $email</p><p><strong>Message:</strong><br>$message</p>",
                'noreply@yourdomain.com',
                'Contact System'
            );
            */

            return $this->output->set_content_type('application/json')->set_output(json_encode([
                'response' => [
                    'status'  => 'ok',
                    'message' => 'Contact message submitted successfully.',
                    'data'    => ['contact_id' => $contact_id],
                    'permission' => 'write'
                ]
            ]));
        } else {
            log_message('error', '[insert_contact] ❌ DB insert failed');
            return $this->_server_error('Failed to submit contact message.');
        }
    }

    // ✅ 400 - Bad Request
private function _bad_request($message, $status_code = 'bad_request')
{
    log_message('error', '❌ ' . $message);
    return $this->output
        ->set_content_type('application/json')
        ->set_status_header(400)
        ->set_output(json_encode([
            'status'  => $status_code,
            'message' => $message
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
}

    // ✅ 409 - Conflict
    private function _conflict($message, $status_code = 'conflict')
    {
        log_message('error', '❗ ' . $message);
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(409)
            ->set_output(json_encode([
                'status'  => $status_code,
                'message' => $message
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    // ✅ 500 - Server Error
    private function _server_error($message = 'Internal Server Error', $status_code = 'server_error')
    {
        log_message('error', '❌ ' . $message);
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(500)
            ->set_output(json_encode([
                'status'  => $status_code,
                'message' => $message
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    // ✅ 401 - Unauthorized
    private function _unauthorized($message = 'Unauthorized', $status_code = 'unauthorized')
    {
        log_message('error', '🔒 ' . $message);
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(401)
            ->set_output(json_encode([
                'status'  => $status_code,
                'message' => $message
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    // ✅ 403 - Forbidden
    private function _forbidden($message = 'Forbidden', $status_code = 'forbidden')
    {
        log_message('error', '🚫 ' . $message);
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(403)
            ->set_output(json_encode([
                'status'  => $status_code,
                'message' => $message
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    // ✅ 404 - Not Found
    private function _not_found($message = 'Resource not found', $status_code = 'not_found')
    {
        log_message('error', '❓ ' . $message);
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(404)
            ->set_output(json_encode([
                'status'  => $status_code,
                'message' => $message
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    // ✅ 200 - OK
    private function _success($data = [], $message = 'Success')
    {
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode([
                'status'  => 'success',
                'message' => $message,
                'data'    => $data
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    // ✅ 201 - Created
    private function _created($data = [], $message = 'Resource created successfully')
    {
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(201)
            ->set_output(json_encode([
                'status'  => 'success',
                'message' => $message,
                'data'    => $data
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    // ✅ Upload handler (unchanged, just uses updated _bad_request)
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
            $this->_bad_request('File upload failed: ' . $error, 'file_upload_failed');
            return false;
        }
    }

    /**
     * 422 Unprocessable Entity (Validation errors)
     */

    // ✅ 422 - Validation Error
    private function _validation_error($errors = [], $message = 'Validation failed', $status_code = 'validation_error')
    {
        log_message('error', '🧪 Validation failed: ' . json_encode($errors));
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(422)
            ->set_output(json_encode([
                'status'  => $status_code,
                'message' => $message,
                'errors'  => $errors
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    /**
     * Get internal payment methods
     * 
     * @param int|null $customer_id
     * @return array
     */

    public function get_internal_payment_methods($customer_id = null)
    {
        try {
            if (!$customer_id) {
                log_message('error', '❌ Missing customer_id in get_internal_payment_methods');
                return [];
            }

            // ✅ Fetch payment methods from acc_coa
            $data = $this->db->select('HeadName, HeadCode')
                ->from('acc_coa')
                ->group_start()
                    ->where('PHeadName', 'Cash')
                    ->or_where('PHeadName', 'Cash at Bank')
                ->group_end()
                ->get()
                ->result();

            // ✅ Build list
            $list = [
                [
                    'HeadCode' => '0',
                    'HeadName' => 'Credit Sale'
                ]
            ];

            foreach ($data as $value) {
                $list[] = [
                    'HeadCode' => $value->HeadCode,
                    'HeadName' => $value->HeadName
                ];
            }

            return $list;

        } catch (Exception $e) {
            log_message('error', '❌ get_internal_payment_methods error: ' . $e->getMessage());
            return [];
        }
    }


    /**
     * Get internal customer commission by email
     * 
     * @param string $email
     * @return array|null
     */

    public function get_internal_customer_commission($email)
    {
        try {
            if (empty($email)) {
                log_message('error', '❌ Missing email in get_internal_customer_commission');
                return null;
            }

            $this->load->model('Api_model');
            $customer = $this->get_internal_customer_by_email($email);

            if (!$customer || !isset($customer->customer_id)) {
                log_message('error', '❌ No customer found for email: ' . $email);
                return null;
            }

            $commission = $this->db->select('commision_value, comission_type')
                ->from('customer_comission')
                ->where('customer_id', $customer->customer_id)
                ->where('status', 1)
                ->order_by('id', 'DESC')
                ->limit(1)
                ->get()
                ->row();

            // Ensure safe access with fallback defaults
            return [
                'customer_id'     => $customer->customer_id,
                'customer_name'   => $customer->customer_name ?? '',
                'customer_email'  => $customer->customer_email ?? '',
                'customer_mobile' => $customer->customer_mobile ?? '',
                'commision_value' => isset($commission->commision_value) ? (float)$commission->commision_value : 0,
                'comission_type'  => $commission->comission_type ?? 'fixed'
            ];

        } catch (Exception $e) {
            log_message('error', '❌ get_internal_customer_commission error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all pending carts by customer ID
     *
     * @param int $customer_id
     * @return array
     */
    public function carts_by_customer_id($customer_id)
    {
        try {
            if (empty($customer_id)) {
                log_message('error', '❌ Missing customer_id in carts_by_customer_id');
                return [];
            }

            $this->load->database();

            $carts = $this->db->select('
                    cart.id as cart_id,
                    cart.customer_id,
                    cart.product_id,
                    cart.batch_id,
                    cart.warehouse_id,
                    cart.quantity,
                    cart.price,
                    cart.discount,
                    cart.note,
                    cart.status,
                    cart.created_at,
                    cart.updated_at,
                    product_information.product_name
                ')
                ->from('cart')
                ->join('product_information', 'product_information.product_id = cart.product_id', 'left')
                ->where('cart.customer_id', $customer_id)
                ->where('cart.status', 'pending')
                ->order_by('cart.created_at', 'DESC')
                ->get()
                ->result_array();

            log_message('debug', '🛒 carts_by_customer_id result: ' . print_r($carts, true));

            return $carts;

        } catch (Exception $e) {
            log_message('error', '❌ carts_by_customer_id error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get cart items by cart_id
     * 
     * @param int $cart_id
     * @return array|null
     */
    public function get_cart_items_by_id($cart_id)
    {
        return $this->db->select('
                cart.id as cart_id,
                cart.customer_id,
                cart.product_id,
                cart.batch_id,
                cart.warehouse_id,
                cart.quantity,
                cart.price,
                cart.discount,
                cart.note,
                cart.status,
                cart.created_at,
                cart.updated_at,
                product_information.product_name
            ')
            ->from('cart')
            ->join('product_information', 'product_information.product_id = cart.product_id', 'left')
            ->where('cart.id', $cart_id)
            ->where('cart.status', 'pending') // Optional, if you want to ensure only active carts are fetched
            ->get()
            ->row_array();
    }

    /**
     * Get internal customer by email
     * 
     * @param string $email
     * @return array|null
     */

    public function get_internal_customer_by_email($email)
    {
        try {
            if (empty($email)) {
                log_message('error', '❌ Missing email in get_internal_customer_by_email');
                return null;
            }

            $this->load->model('Api_model');
            $customer = $this->Api_model->get_customer_by_email($email);

            if (!$customer || !isset($customer->customer_id)) {
                log_message('error', '❌ No customer found for email: ' . $email);
                return null;
            }

            return [
                'customer_id'     => $customer->customer_id,
                'customer_name'   => $customer->customer_name ?? '',
                'customer_email'  => $customer->customer_email ?? '',
                'customer_mobile' => $customer->customer_mobile ?? ''
            ];

        } catch (Exception $e) {
            log_message('error', '❌ get_internal_customer_by_email error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get internal customer by ID
     * 
     * @param int $customer_id
     * @return array|null
     */

    public function get_internal_customer_by_id($customer_id)
    {
        try {
            if (empty($customer_id)) {
                log_message('error', '❌ Missing customer_id in get_internal_customer_by_id');
                return null;
            }

            $this->load->model('Api_model');
            $customer = $this->Api_model->get_customer_by_id($customer_id);

            if (!$customer || !isset($customer->customer_id)) {
                log_message('error', '❌ No customer found for ID: ' . $customer_id);
                return null;
            }

            return [
                'customer_id'     => $customer->customer_id,
                'customer_name'   => $customer->customer_name ?? '',
                'customer_email'  => $customer->customer_email ?? '',
                'customer_mobile' => $customer->customer_mobile ?? ''
            ];

        } catch (Exception $e) {
            log_message('error', '❌ get_internal_customer_by_id error: ' . $e->getMessage());
            return null;
        }
    }


    public function internal_insert_invoice($input = null)
    {
        header('Content-Type: application/json');
        log_message('debug', '🧾 Internal insert_invoice called');

        // ✅ Parse input
        if (empty($input)) {
            $input = json_decode(file_get_contents("php://input"), true);
            log_message('debug', '📥 Raw request body from php://input: ' . print_r($input, true));
        } else {
            log_message('debug', '📥 Raw request body from function argument: ' . print_r($input, true));
        }

        if (empty($input)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input']);
            return;
        }

        $customer_id = $input['customer_id'] ?? null;
        if (!$customer_id) {
            echo json_encode(['status' => 'error', 'message' => 'Missing customer_id in request body']);
            return;
        }

        $createby = $input['createby'] ?? $customer_id;
        $invoice_by = $input['invoice_by'] ?? 2;

        $this->load->library(['session', 'occational', 'smsgateway', 'Sendmail_lib']);
        $this->load->model('invoice/Invoice_model', 'invoice_model');
        $this->load->model('account/Accounts_model', 'accounts_model');

        $this->session->set_userdata('id', $createby);

        $finyear = financial_year();
        if ($finyear <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Please Create Financial Year First']);
            return;
        }

        $invoice_id = $this->invoice_generator();
        log_message('debug', "🧾 Generated invoice_id = $invoice_id");

        $details = $input['detailsinfo'] ?? [];
        $grand_total = 0;
        $total_discount = floatval($input['total_discount'] ?? 0);

        foreach ($details as $item) {
            $qty = floatval($item['product_quantity']);
            $rate = floatval($item['product_rate']);
            $grand_total += $qty * $rate;
        }

        $paid_amount = floatval($input['paid_amount']);
        $due_amount = max(0, $grand_total - $paid_amount - $total_discount);

        $_POST = [
            'invoice_id'           => $invoice_id,
            'invoice'              => $invoice_id,
            'customer_id'          => $customer_id,
            'createby'             => $createby,
            'invoice_by'           => $invoice_by,
            'paid_amount'          => $paid_amount,
            'due_amount'           => $due_amount,
            'total_discount'       => $total_discount,
            'total_tax'            => $input['total_tax'] ?? 0,
            'invoice_date'         => $input['invoice_date'] ?? date('Y-m-d'),
            'inva_details'         => $input['inva_details'] ?? 'API Invoice',
            'payment_type'         => $input['payment_type'],
            'delivery_note'        => 0,
            'status'               => $input['status'] ?? 1,
            'invoice_discount'     => 0,
            'total_vat_amnt'       => 0,
            'previous'             => 0,
            'shipping_cost'        => 0,
            'is_credit'            => ($input['payment_type'] == 0) ? 1 : 0,
            'multipaytype'         => [$input['payment_type']],
            'pamount_by_method'    => [$paid_amount],
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
            'grand_total_price'    => $grand_total
        ];

        foreach ($details as $item) {
            $qty = floatval($item['product_quantity']);
            $rate = floatval($item['product_rate']);
            $total = $qty * $rate;

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

        $inserted_invoice_id = $this->invoice_model->invoice_entry($invoice_id);
        log_message('debug', "✅ Invoice inserted, ID: $inserted_invoice_id");

        $setting_data = $this->db->select('is_autoapprove_v')->from('web_setting')->where('setting_id', 1)->get()->row();
        if ($setting_data && $setting_data->is_autoapprove_v == 1) {
            $this->autoapprove($inserted_invoice_id);
            log_message('debug', "✅ Auto-approved voucher for $inserted_invoice_id");
        }

        $cusinfo = $this->db->get_where('customer_information', ['customer_id' => $customer_id])->row();
        $customer_name = $cusinfo->customer_name ?? 'Unknown';
        $customer_email = $cusinfo->customer_email ?? '';
        $customer_mobile = $cusinfo->customer_mobile ?? '';

        $config_data = $this->db->get('sms_settings')->row();
        if ($config_data && $config_data->isinvoice == 1 && !empty($customer_mobile)) {
            $message = 'Mr.' . $customer_name . ', You have purchased ' . number_format($grand_total, 2) . ' and paid ' . $paid_amount;
            $this->smsgateway->send([
                'apiProvider' => 'nexmo',
                'username'    => $config_data->api_key,
                'password'    => $config_data->api_secret,
                'from'        => $config_data->from,
                'to'          => $customer_mobile,
                'message'     => $message
            ]);
            log_message('debug', "✅ SMS sent to customer {$customer_mobile}");
        }

        $smtp = $this->db->get('email_config')->row_array();
        $admin_subject = "🧾 New Invoice Created - {$invoice_id}";
        $admin_message = "<h4>New Invoice Notification</h4>
            <p><strong>Invoice ID:</strong> {$invoice_id}</p>
            <p><strong>Customer:</strong> {$customer_name}</p>
            <p><strong>Email:</strong> {$customer_email}</p>
            <p><strong>Total Amount:</strong> " . number_format($grand_total, 2) . "</p>
            <p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

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
            $customer_message = "<h3>Dear {$customer_name},</h3>
                <p>Thank you for your purchase. Your invoice has been created successfully.</p>
                <p><strong>Invoice ID:</strong> {$invoice_id}</p>
                <p><strong>Total:</strong> " . number_format($grand_total, 2) . "</p>
                <p>If you have any questions, please contact support.</p>";

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
            'message'    => 'Invoice created successfully via internal_insert_invoice()'
        ]);
    }

    private function internal_insert_invoice_payment(array $input)
    {
        $this->db->trans_begin();

        try {
            // ✅ Handle payment_ref_doc upload
            $payment_ref_doc_path = '';
            if (!empty($_FILES['payment_ref_doc']['name'])) {
                $upload_dir = './uploads/payment_refs/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $this->load->library('upload');

                $original_name = pathinfo($_FILES['payment_ref_doc']['name'], PATHINFO_FILENAME);
                $ext = pathinfo($_FILES['payment_ref_doc']['name'], PATHINFO_EXTENSION);
                $unique_name = $original_name . '_' . time() . '_' . uniqid() . '.' . $ext;

                $_FILES['payment_ref_doc']['name'] = $unique_name;

                $config['upload_path']   = $upload_dir;
                $config['allowed_types'] = 'pdf|jpg|jpeg|png';
                $config['file_name']     = $unique_name;
                $config['max_size']      = 2048;

                $this->upload->initialize($config);

                if ($this->upload->do_upload('payment_ref_doc')) {
                    $payment_ref_doc_path = 'uploads/payment_refs/' . $unique_name;
                } else {
                    log_message('error', '❌ Upload error: ' . $this->upload->display_errors());
                    return ['status' => 'error', 'message' => 'File upload failed: ' . strip_tags($this->upload->display_errors())];
                }
            } else {
                // fallback if file not uploaded this time
                $payment_ref_doc_path = $input['payment_ref_doc'] ?? '';
            }

            // Step 1: Insert invoice_payment (main)
            $invoice_data = [
                'invoice_date'     => $input['invoice_date'] ?? date('Y-m-d'),
                'createby'         => $input['createby'],
                'customer_id'      => $input['customer_id'],
                'paid_amount'      => $input['paid_amount'],
                'due_amount'       => $input['due_amount'] ?? 0,
                'total_discount'   => $input['total_discount'] ?? 0,
                'total_tax'        => $input['total_tax'] ?? 0,
                'total_amount'     => 0, // Temporary, will update later
                'payment_type'     => $input['payment_type'] ?? 1,
                'payment_ref_doc'  => $payment_ref_doc_path,
                'payment_ref'      => $input['payment_ref'] ?? '',
                'transaction_ref'  => $input['transaction_ref'],
                'status'           => $input['status'] ?? 2,
                'created_at'       => date('Y-m-d H:i:s'),
            ];

            $this->db->insert('invoice_payment', $invoice_data);
            $invoice_id = $this->db->insert_id();
            log_message('debug', "✅ Invoice payment inserted with ID: $invoice_id");

            // Step 2: Insert invoice_payment_details
            $total_value = 0;
            foreach ($input['detailsinfo'] as $item) {
                $qty = (float) $item['product_quantity'];
                $rate = (float) $item['product_rate'];
                $discount_percent = (float) ($item['discount'] ?? 0);
                $product_id = $item['product_id'];
                $serial_no = $item['serial_no'] ?? '';

                // ✅ Fetch warehouse_id from cart table
                $cart_row = $this->db->select('warehouse_id')
                    ->from('cart')
                    ->where('product_id', $product_id)
                    ->where('status', 'pending')
                    ->where('customer_id', $input['customer_id'])
                    ->order_by('id', 'desc')
                    ->limit(1)
                    ->get()
                    ->row();

                $warehouse_id = $cart_row->warehouse_id ?? null;

                $line_total = $qty * $rate;
                $discount_amount = ($discount_percent / 100) * $line_total;
                $final_value = $line_total - $discount_amount;

                $total_value += $final_value;

                $details_data = [
                    'invoice_id'       => $invoice_id,
                    'product_id'       => $product_id,
                    'product_quantity' => $qty,
                    'product_rate'     => $rate,
                    'discount'         => $discount_percent,
                    'total_value'      => $final_value,
                    'serial_no'        => $serial_no,
                    'warehouse_id'     => $warehouse_id,
                    'created_at'       => date('Y-m-d H:i:s')
                ];

                $this->db->insert('invoice_payment_details', $details_data);
            }

            // Step 3: Update invoice_payment.total_amount with actual sum
            $this->db->where('id', $invoice_id)->update('invoice_payment', ['total_amount' => $total_value]);

            // Step 4: Commit or rollback
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                log_message('error', '❌ Transaction failed. Rolling back.');
                return ['status' => 'error', 'message' => 'Invoice payment failed'];
            }

            $this->db->trans_commit();
            return [
                'status'     => 'success',
                'invoice_id' => $invoice_id,
                'message'    => 'Invoice payment inserted successfully'
            ];
        } catch (Exception $e) {
            $this->db->trans_rollback();
            log_message('error', '❌ Exception in invoice insert: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Something went wrong'];
        }
    }

}
