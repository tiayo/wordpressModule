<?php

namespace Foru\Product;

use Foru\Api\Verification;
use Foru\Model\TermRelationships;
use Foru\Model\Terms;
use Foru\Model\TermTaxonomy;

class Category
{
    protected $term_relationships;
    protected $term_taxonomy;
    protected $terms;

    public function __construct()
    {
        $this->terms = new Terms();
        $this->term_relationships = new TermRelationships();
        $this->term_taxonomy = new TermTaxonomy();
    }

    /**
     * 验证token.
     *
     * @return string/array()
     */
    public function verification()
    {
        Verification::verification();

        return $this->get('api');
    }

    /**
     * 获取所有分类.
     *
     * @param null $option 设置操作
     *
     * @return string
     */
    public function get($option = null)
    {
        $all_category = $this->term_taxonomy
            ->select('term_taxonomy_id', 'term_id', 'parent')
            ->where('taxonomy', 'product_cat')
            ->get()
            ->toArray();

        foreach ($all_category as $row) {
            $name = $this->terms
                ->select('name')
                ->where('term_id', $row['term_id'])
                ->first()->name;
            $row['name'] = $name;
            $category[] = $row;
        }

        if ($option == 'api') {
            return $this->api($category);
        }

        return $this->tree($category);
    }

    /**
     * 生成树型数组.
     *
     * @param $items
     *
     * @return string
     */
    public function tree($items)
    {
        $childs = [];

        foreach ($items as &$item) {
            $childs[$item['parent']][] = &$item;
        }

        unset($item);

        foreach ($items as &$item) {
            if (isset($childs[$item['term_id']])) {
                $item['childs'] = $childs[$item['term_id']];
            }
        }

        return $this->procHtml($childs[0]);
    }

    /**
     * 转换成html.
     *
     * @param $tree
     *
     * @return string
     */
    public function procHtml($tree)
    {
        $html = '';
        foreach ($tree as $t) {
            $t['childs'] = isset($t['childs']) ? $t['childs'] : null; //No report index does not exist
            if ($t['childs'] == '') {
                $html .= "<li><option value='{$t['term_id']}'>{$this->dash($t)}{$t['name']}</option></li>";
            } else {
                $html .= "<li><option value='{$t['term_id']}'>{$this->dash($t)}{$t['name']}</option>";
                $html .= $this->procHtml($t['childs']);
                $html = $html.'</li>';
            }
        }

        return $html ? '<ul>'.$html.'</ul>' : $html;
    }

    /**
     * 给显示在前端的分类加上层级结构.
     *
     * @param $data
     *
     * @return null|string
     */
    public function dash($data)
    {
        $str = null;
        if ($data['parent'] == 0) {
            return $str;
        }

        $id = $data['parent'];
        for ($i = 1; ; ++$i) {
            $parent = $this->term_taxonomy
                ->select('parent')
                ->where('term_id', $id)
                ->first()
                ->toArray();

            if ($parent['parent'] == 0) {
                break;
            }

            $id = $parent['parent'];
        }

        for ($x = 0; $x < $i; ++$x) {
            $str .= '--';
        }

        return $str;
    }

    /**
     * 处理返回给api的分类数据.
     *
     * @param $categary
     *
     * @return array()
     */
    public function api($categary)
    {
        $result = [];
        foreach ($categary as $key => $item) {
            $result[$key]['id'] = $item['term_taxonomy_id'];
            $result[$key]['pid'] = $item['parent'];
            $result[$key]['name'] = $item['name'];
        }

        return $result;
    }
}
