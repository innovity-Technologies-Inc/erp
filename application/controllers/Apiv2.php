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
     * @OA\Post(
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

        $category_list = $this->Api_model->category_list(); // now returns all

        if (!empty($category_list)) {
            $json['response'] = [
                'status'     => 'ok',
                'categories' => $category_list,
                'total_val'  => count($category_list),
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

        $start = $this->input->get('start');
        if ($start) {
            $start = ($start == 1 ? 0 : $start);
            $products = $this->Api_model->product_list($limit = 15, $start);
        } else {
            $products = $this->Api_model->searchproduct_list();
        }

        if (!empty($products)) {
            foreach ($products as $k => $v) {
                $s = $this->db->select('sum(quantity) as totalSalesQnty')->where('product_id', $v['product_id'])->get('invoice_details')->row();
                $p = $this->db->select('sum(quantity) as totalBuyQnty')->where('product_id', $v['product_id'])->get('product_purchase_details')->row();
                $stokqty = $p->totalBuyQnty - $s->totalSalesQnty;

                $config['cacheable'] = true;
                $config['cachedir'] = '';
                $config['errorlog'] = '';
                $config['quality'] = true;
                $config['size'] = '1024';
                $config['black'] = array(224, 255, 255);
                $config['white'] = array(70, 130, 180);
                $this->ciqrcode->initialize($config);

                $params['data'] = $products[$k]['product_id'];
                $products[$k]['stock_qty'] = (!empty($stokqty) ? $stokqty : 0);
                $params['level'] = 'H';
                $params['size'] = 10;
                $image_name = $products[$k]['product_id'] . '.png';
                $params['savename'] = FCPATH . 'my-assets/image/qr/' . $image_name;
                $this->ciqrcode->generate($params);

                $products[$k]['product_info_bybarcode'] = $this->Api_model->product_info_bybarcode($products[$k]['product_id']);
                $products[$k]['qr_code'] = base_url('my-assets/image/qr/' . $image_name);
                $products[$k]['bar_code'] = base_url('Cbarcode/barcode_generator/' . $products[$k]['product_id']);
            }
        }

        if (!empty($products)) {
            $json['response'] = array(
                'status' => 'ok',
                'product_list' => $products,
                'total_val' => $this->db->count_all("product_information"),
            );
        } else {
            $json['response'] = array(
                'status' => 'error',
                'product_list' => [],
                'message' => 'No Product Found',
            );
        }

        echo json_encode($json, JSON_UNESCAPED_UNICODE);
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

        // $this->load->library('session');

        $customer_email = $this->input->post('email');
        $customer_mobile = $this->input->post('mobile');
        $password = $this->input->post('password');

        log_message('debug', "📥 Email: $customer_email | Mobile: $customer_mobile");

        // === Validations ===
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

        // === Check for duplicates first ===
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

        // === File Upload ===
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

        // === Prepare customer data ===
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

            // Modules::run('Sendmail/send_verification', $customer_email, $verify_url);

            $this->load->library('sendmail_library');
            $this->sendmail_library->send_verification($email, $verify_url);

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
        if (!$token) show_error('Invalid verification link', 400);

        $record = $this->db->get_where('email_verification_tokens', ['token' => $token])->row();
        if (!$record) show_error('Token not found or expired', 404);

        // Update both tables' status to 0
        $this->db->where('customer_id', $record->customer_id)->update('customer_information', ['status' => 0]);
        $this->db->where('customer_id', $record->customer_id)->update('customer_auth', ['status' => 0]);

        // Get customer info
        $customer = $this->db->get_where('customer_information', ['customer_id' => $record->customer_id])->row();

        // Send confirmation email using Sendmail controller
        Modules::run('Sendmail/send_confirmation', $customer->customer_email);

        echo "<h2>Email Verified</h2><p>You can now wait for support or call <strong>+1234567890012</strong>.</p>";
    }
}
