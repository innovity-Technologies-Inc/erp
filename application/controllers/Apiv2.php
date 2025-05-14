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
        $input = json_decode(trim(file_get_contents("php://input")), true);
        if (!isset($input['username']) || !isset($input['password'])) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode(['status' => 'error', 'message' => 'Username and password required']));
        }

        $user = $this->Api_model->verify_api_user_credentials($input['username'], $input['password']);
        if (!$user) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(401)
                ->set_output(json_encode(['status' => 'error', 'message' => 'Invalid credentials']));
        }

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

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => 'success',
                'access_token' => $access_token,
                'refresh_token' => $refresh_token,
                'expires_in' => $this->jwt_ttl
            ]));
    }


    /**
     * @OA\Post
     * (
     *     path="/apiv2/refresh_token",
     *     tags={"Authentication"},
     *     summary="Refresh access token",
     *     description="Accepts a valid refresh token and returns a new access token.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"refresh_token"},
     *             @OA\Property(property="refresh_token", type="string", example="your_refresh_token_here")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token refreshed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="expires_in", type="integer", example=3600)
     *         )
     *     ),
     *     @OA\Response(response=400, description="Refresh token required"),
     *     @OA\Response(response=401, description="Invalid or expired refresh token")
     * )
     */

    public function refresh_token()
    {
        $input = json_decode(trim(file_get_contents("php://input")), true);
        $refresh_token = $input['refresh_token'] ?? null;

        if (!$refresh_token) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode(['status' => 'error', 'message' => 'Refresh token required']));
        }

        try {
            $decoded = JWT::decode($refresh_token, new Key($this->jwt_key, $this->jwt_algo));
            $user = $this->Api_model->get_api_user_by_id($decoded->uid);

            if (!$user) {
                throw new Exception('User not found');
            }

            $new_token = JWT::encode([
                'iat' => time(),
                'exp' => time() + $this->jwt_ttl,
                'uid' => $user['id'],
                'username' => $user['username'],
                'usertype' => $user['usertype']
            ], $this->jwt_key, $this->jwt_algo);

            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => 'success',
                    'access_token' => $new_token,
                    'expires_in' => $this->jwt_ttl
                ]));
        } catch (Exception $e) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(401)
                ->set_output(json_encode(['status' => 'error', 'message' => 'Invalid or expired refresh token']));
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


    public function product_search()
    {
        // Start execution timer
        $start_time = microtime(true);
        log_message('debug', 'product_search() initiated');

        // Authentication (unchanged)
        $user = $this->authenticate_token();
        if (!$user) {
            log_message('warning', 'Authentication failed - invalid access token');
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(401)
                ->set_output(json_encode([
                    'status' => 'error',
                    'message' => 'Unauthorized. Please provide a valid access token.'
                ]));
        }
        log_message('debug', 'First layer authentication successful');

        // Second layer authentication (unchanged)
        $second_token = $this->input->get_request_header('X-Second-Token');
        $view_sensitive_data = false;

        if ($second_token) {
            try {
                $decoded = JWT::decode($second_token, new Key($this->jwt_key, $this->jwt_algo));
                if (isset($decoded->customer_id)) {
                    log_message('info', 'Second Layer Token Decoded Successfully for customer: ' . $decoded->customer_id);
                    $view_sensitive_data = true;
                }
            } catch (Exception $e) {
                log_message('error', 'Second Layer Token Decode Failed: ' . $e->getMessage());
            }
        }

        // Get and validate input
        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            log_message('error', 'Invalid JSON input: ' . json_last_error_msg());
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode([
                    'status' => 'error',
                    'message' => 'Invalid JSON input'
                ]));
        }

        // Process input parameters with explicit table references
        $product_id   = isset($input['product_id']) ? $this->db->escape_str(trim($input['product_id'])) : '';
        $product_name = isset($input['product_name']) ? $this->db->escape_str(trim($input['product_name'])) : '';
        $category_id  = isset($input['category_id']) ? (int)$input['category_id'] : 0;
        $min_price    = isset($input['min_price']) ? (float)$input['min_price'] : null;
        $max_price    = isset($input['max_price']) ? (float)$input['max_price'] : null;
        $limit        = isset($input['limit']) && (int)$input['limit'] > 0 ? (int)$input['limit'] : 10;
        $page         = isset($input['page']) && (int)$input['page'] > 0 ? (int)$input['page'] : 1;
        $offset       = ($page - 1) * $limit;

        // Log input parameters
        log_message('debug', 'Search parameters:', [
            'product_id' => $product_id,
            'product_name' => $product_name,
            'category_id' => $category_id,
            'min_price' => $min_price,
            'max_price' => $max_price,
            'limit' => $limit,
            'page' => $page
        ]);

        // Initialize query with explicit table references
        $this->db->select('product_information.*')
                ->from('product_information');

        // Apply product_id filter with table prefix
        if (!empty($product_id)) {
            $this->db->where('product_information.product_id', $product_id);
            log_message('debug', 'Applied product_id filter: ' . $product_id);
        }
        
        // Apply product_name filter with table prefix
        if (!empty($product_name)) {
            $product_name = str_replace('%', '', $product_name);
            $search_term = str_replace(' ', '%', $product_name);
            $this->db->group_start()
                ->like('product_information.product_name', $product_name, 'both')
                ->or_like('product_information.product_name', $search_term, 'both')
                ->group_end();
            log_message('debug', 'Applied product_name filter: ' . $product_name);
        }

        // Apply category filter with explicit table references
        if ($category_id > 0) {
            // First verify the category exists
            $category_exists = $this->db->from('product_category')
                                    ->where('category_id', $category_id)
                                    ->count_all_results() > 0;
            
            if ($category_exists) {
                $all_category_ids = $this->get_all_related_category_ids($category_id);
                log_message('debug', 'Category IDs for filtering:', $all_category_ids);
                
                if (!empty($all_category_ids)) {
                    $this->db->where_in('product_information.category_id', $all_category_ids);
                }
            } else {
                log_message('warning', 'Invalid category_id provided: ' . $category_id);
                // Return empty result for invalid categories
                return $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'status' => 'success',
                        'total_count' => 0,
                        'matched_count' => 0,
                        'page' => $page,
                        'page_count' => 0,
                        'limit' => $limit,
                        'result' => []
                    ]));
            }
        }

        // Price filters with explicit table references
        if (!is_null($min_price)) {
            $this->db->where('CAST(product_information.price AS DECIMAL(10,2)) >=', $min_price);
            log_message('debug', 'Applied min_price filter: ' . $min_price);
        }
        if (!is_null($max_price)) {
            $this->db->where('CAST(product_information.price AS DECIMAL(10,2)) <=', $max_price);
            log_message('debug', 'Applied max_price filter: ' . $max_price);
        }

        // Get total count before pagination
        $total_query = clone $this->db;
        $total_count = $total_query->count_all_results();
        log_message('debug', 'Total matching records before pagination: ' . $total_count);

        // Apply pagination
        $this->db->limit($limit, $offset);
        
        // Get final results
        $query = $this->db->get();
        
        if (!$query) {
            $error = $this->db->error();
            log_message('error', 'Database query failed: ' . $error['message']);
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(500)
                ->set_output(json_encode([
                    'status' => 'error',
                    'message' => 'Database query failed',
                    'error' => $error
                ]));
        }

        $products = $query->result_array();
        log_message('debug', 'Retrieved ' . count($products) . ' products');

        // Preload category names for better performance
        $category_map = [];
        $categories = $this->db->select('category_id, category_name')
                            ->get('product_category')
                            ->result();
        foreach ($categories as $cat) {
            $category_map[$cat->category_id] = $cat->category_name;
        }

        // Process each product
        foreach ($products as $k => $v) {
            // Fix image paths
            if (!empty($products[$k]['image'])) {
                $products[$k]['image'] = base_url(str_replace('./', '', $products[$k]['image']));
            }

            
            // Add category name
            $products[$k]['category_name'] = $category_map[$v['category_id']] ?? 'Uncategorized';

            // Handle sensitive data
            if ($view_sensitive_data) {
                // Convert price to float since it's stored as varchar
                $products[$k]['price'] = (float)$products[$k]['price'];
            } else {
                unset($products[$k]['price']);
            }

            // Add QR code and barcode URLs
            $products[$k]['qr_code'] = base_url('my-assets/image/qr/' . $v['product_id'] . '.png');
            $products[$k]['bar_code'] = base_url('Cbarcode/barcode_generator/' . $v['product_id']);
        }

        // Calculate execution time
        $execution_time = microtime(true) - $start_time;
        log_message('debug', 'product_search() completed in ' . $execution_time . ' seconds');

        // Return final response
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => 'success',
                'total_count' => $total_count,
                'matched_count' => count($products),
                'page' => $page,
                'page_count' => ceil($total_count / $limit),
                'execution_time' => round($execution_time, 4),
                'limit' => $limit,
                'result' => $products
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
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

        $customer_email = $this->input->post('email');
        $customer_mobile = $this->input->post('mobile');
        $password = $this->input->post('password');

        log_message('debug', "📥 Email: $customer_email | Mobile: $customer_mobile");

        if (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
            log_message('error', '❌ Invalid email format: ' . $customer_email);
            return $this->output->set_content_type('application/json')->set_status_header(400)
                ->set_output(json_encode(['status' => 'error', 'message' => 'Invalid email format']));
        }

        if (!preg_match('/^[0-9]+$/', $customer_mobile)) {
            log_message('error', '❌ Mobile number format error: ' . $customer_mobile);
            return $this->output->set_content_type('application/json')->set_status_header(400)
                ->set_output(json_encode(['status' => 'error', 'message' => 'Mobile must be digits only']));
        }

        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            log_message('error', '❌ Password missing special character');
            return $this->output->set_content_type('application/json')->set_status_header(400)
                ->set_output(json_encode(['status' => 'error', 'message' => 'Password must include at least one special character']));
        }

        $existsMobile = $this->db->where('customer_mobile', $customer_mobile)->get('customer_information')->row();
        if ($existsMobile) {
            log_message('error', '❗ Duplicate customer mobile found: ' . $customer_mobile);
            return $this->output->set_content_type('application/json')->set_status_header(409)
                ->set_output(json_encode(['status' => 'error', 'message' => 'Mobile number already exists']));
        }

        $existsEmail = $this->db->where('username', $customer_email)->get('customer_auth')->row();
        if ($existsEmail) {
            log_message('error', '❗ Duplicate email found: ' . $customer_email);
            return $this->output->set_content_type('application/json')->set_status_header(409)
                ->set_output(json_encode(['status' => 'error', 'message' => 'Email already registered']));
        }

        $sales_permit = '';
        if (!empty($_FILES['sales_permit']['name'])) {
            log_message('debug', '📎 Uploading sales permit file...');
            $config['upload_path']   = './uploads/sales_permits/';
            $config['allowed_types'] = 'jpg|jpeg|png|pdf|doc|docx';
            $config['max_size']      = 2048;
            $config['file_name']     = time() . '_' . $_FILES['sales_permit']['name'];

            if (!is_dir($config['upload_path'])) {
                mkdir($config['upload_path'], 0755, true);
            }

            $this->load->library('upload', $config);
            $this->load->library('session');

            if ($this->upload->do_upload('sales_permit')) {
                $upload_data = $this->upload->data();
                $sales_permit = $upload_data['file_name'];
                log_message('debug', '✅ File uploaded: ' . $sales_permit);
            } else {
                $error = strip_tags($this->upload->display_errors());
                log_message('error', '❌ File upload failed: ' . $error);
                echo json_encode(['response' => ['status' => 'error', 'message' => 'File upload failed: ' . $error]]);
                return;
            }
        }

        log_message('debug', '📥 Preparing customer data...');
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

        log_message('debug', '📝 Inserting customer record...');
        if ($this->Api_model->customer_create($data)) {
            $customer_id = $this->db->insert_id();
            log_message('debug', '✅ Customer inserted with ID: ' . $customer_id);

            $coa = $this->Api_model->customerheadcode();
            $headcode = ($coa && $coa->HeadCode != NULL) ? $coa->HeadCode + 1 : "102030000001";
            $c_acc = $customer_id . '-' . $this->input->post('customer_name');

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
            log_message('debug', '📘 Chart of Account created');

            $this->Api_model->customer_previous_balance_add($this->input->post('previous_balance'), $customer_id);
            log_message('debug', '💵 Previous balance processed');

            $this->db->insert('customer_auth', [
                'customer_id' => $customer_id,
                'username'    => $customer_email,
                'password'    => password_hash($password, PASSWORD_BCRYPT),
                'status'      => 3
            ]);
            log_message('debug', '🔐 Customer login created (status 3)');

            $token = bin2hex(random_bytes(32));
            $this->db->insert('email_verification_tokens', [
                'customer_id' => $customer_id,
                'token' => $token
            ]);
            log_message('debug', '📩 Email token saved: ' . $token);

            log_message('debug', '🧠 Checking if CI session is initialized...');

            if (!isset($this->session)) {
                log_message('debug', '🔍 $this->session is not set — attempting to load CI session library.');
                $this->load->library('session');

                if (isset($this->session)) {
                    log_message('debug', '✅ CI session library loaded successfully.');
                } else {
                    log_message('error', '❌ Failed to load CI session library.');
                }
            } else {
                log_message('debug', '🧠 $this->session is already initialized.');
            }

            $this->session->set_userdata('registered_customer_id', $customer_id);
            $this->session->set_userdata('registered_customer_email', $customer_email);
            log_message('debug', '📦 Session data set: registered_customer_id = ' . $customer_id . ', registered_customer_email = ' . $customer_email);

            $verify_url = base_url("apiv2/verify_email?token=$token");

            $this->load->library('sendmail_lib');
            $email = $customer_email;
            log_message('debug', "📨 Sending verification email to: $email with URL: $verify_url");

            if (empty($email) || empty($verify_url)) {
                log_message('error', "❌ Missing parameters in send_verification | Email: $email | URL: $verify_url");
            } else {
                $this->sendmail_lib->send(
                    $email,
                    'Verify your email address',
                    "<h3>Registration Successful!</h3><p>Thank you for registering. Please click the button below to verify your email:</p><p><a href='$verify_url' style='padding:10px 20px; background:#4CAF50; color:#fff; text-decoration:none;'>Verify Email</a></p><p>If the button above doesn't work, copy and paste this URL into your browser:</p><p>$verify_url</p>",
                    'noreply@paysenz.com',
                    'Deshi Shad'
                );
            }

            log_message('debug', '✉️ Verification email triggered');

            echo json_encode([
                'response' => [
                    'status'     => 'ok',
                    'message'    => 'Customer created. Verification email sent.',
                    'permission' => 'write'
                ]
            ]);
        } else {
            log_message('error', '❌ Customer insertion failed');
            echo json_encode([
                'response' => [
                    'status' => 'error',
                    'message' => 'Please try again',
                    'permission' => 'read'
                ]
            ]);
        }
    }


    public function verify_email()
    {
        $token = $this->input->get('token');
        if (!$token) {
            show_error('Invalid verification link', 400);
        }

        $record = $this->db->get_where('email_verification_tokens', ['token' => $token])->row();
        if (!$record) {
            show_error('Token not found or expired', 404);
        }

        // Update both tables' status to 0
        $this->db->where('customer_id', $record->customer_id)->update('customer_information', ['status' => 0]);
        $this->db->where('customer_id', $record->customer_id)->update('customer_auth', ['status' => 0]);

        // Get customer info
        $customer = $this->db->get_where('customer_information', ['customer_id' => $record->customer_id])->row();

        // Prepare confirmation message
        $email_body = '
            <html>
            <head><title>Email Verified</title></head>
            <body>
                <h3>Email Verified!</h3>
                <p>You have successfully verified your email.</p>
                <p>Deshi Shad support team will now contact you to activate your account.</p>
                <p>You may also call us at <strong>+1234567890012</strong>.</p>
            </body>
            </html>
        ';

        // Send confirmation using Sendmail_lib
        $this->load->library('sendmail_lib');
        log_message('debug', "📨 Sending email verification confirmation to: {$customer->customer_email}");

        $this->sendmail_lib->send(
            $customer->customer_email,
            'Email Verified Successfully',
            $email_body,
            'noreply@paysenz.com',
            'Deshi Shad'
        );

        // Output confirmation HTML
        echo "<h2>Email Verified</h2><p>You can now wait for support or call <strong>+1234567890012</strong>.</p>";
    }

    public function second_layer_login()
    {
        // Step 1: Get the JSON input
        $input = json_decode(trim(file_get_contents("php://input")), true);

        // Step 2: Validate the input
        if (!isset($input['username']) || !isset($input['password'])) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode([
                    'status' => 'error',
                    'message' => 'Username and password are required.'
                ]));
        }

        $username = $input['username'];
        $password = $input['password'];

        // Step 3: Check if user exists and status is Active (1)
        $user = $this->db->select('id, customer_id, password, status')
                        ->from('customer_auth')
                        ->where('username', $username)
                        ->where('status', 1) // Only Active status
                        ->get()
                        ->row();

        if (!$user) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(401)
                ->set_output(json_encode([
                    'status' => 'error',
                    'message' => 'Invalid credentials or account not active.'
                ]));
        }

        // Step 4: Verify Password
        if (!password_verify($password, $user->password)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(401)
                ->set_output(json_encode([
                    'status' => 'error',
                    'message' => 'Invalid password.'
                ]));
        }

        // Step 5: Generate the 2nd Layer Token (JWT)
        $second_layer_token = JWT::encode([
            'iat' => time(),
            'exp' => time() + 3600, // 1 hour expiry
            'customer_id' => $user->customer_id,
            'username'    => $username,
        ], $this->jwt_key, $this->jwt_algo);

        // Step 6: Respond with the second layer token
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => 'success',
                'message' => '2nd Layer Login Successful',
                'second_layer_token' => $second_layer_token,
                'expires_in' => 3600 // 1 hour
            ]));
    }
}
