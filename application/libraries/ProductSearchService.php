<?php

class ProductSearchService
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    public function buildProductSearchQuery($params)
    {
        $db = $this->CI->db;

        $db->select('product_information.*')
           ->from('product_information');

        if (!empty($params['product_id'])) {
            $db->where('product_information.product_id', $params['product_id']);
            log_message('debug', 'Priority filter: product_id only applied: ' . $params['product_id']);
        } else {
            if (!empty($params['product_name'])) {
                $product_name = str_replace('%', '', $params['product_name']);
                $search_term = str_replace(' ', '%', $product_name);
                $db->group_start()
                   ->like('product_information.product_name', $product_name, 'both')
                   ->or_like('product_information.product_name', $search_term, 'both')
                   ->group_end();
                log_message('debug', 'product_name filter applied: ' . $product_name);
            }

            if (!empty($params['category_id'])) {
                $category_id = (int)$params['category_id'];
                $category_exists = $db->from('product_category')->where('category_id', $category_id)->count_all_results() > 0;

                if ($category_exists) {
                    $all_ids = $this->CI->get_all_related_category_ids($category_id);
                    if (!empty($all_ids)) {
                        $db->where_in('product_information.category_id', $all_ids);
                    }
                } else {
                    $db->where('product_information.category_id', $category_id);
                    log_message('warning', 'Fallback category_id applied directly: ' . $category_id);
                }
            }

            if (isset($params['min_price'])) {
                $db->where('CAST(product_information.price AS DECIMAL(10,2)) >=', (float)$params['min_price']);
            }

            if (isset($params['max_price'])) {
                $db->where('CAST(product_information.price AS DECIMAL(10,2)) <=', (float)$params['max_price']);
            }
        }

        return $db;
    }
}