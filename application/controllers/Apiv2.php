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
}
