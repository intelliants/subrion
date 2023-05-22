<?php

function smarty_function_html_categories($params)
{
    $categories = $params['source'];
    $rows = isset($params['rows']) ? $params['rows'] : false;
    $columns = $params['columns'] ? $params['columns'] : 2;
    $urlPrefix = '';
    $show_amount = isset($params['show_amount']) && false == $params['show_amount'] ? false : true;
    $displayIcon = isset($params['show_icon']) && false == $params['show_icon'] ? false : true;
    $wrap = isset($params['wrap']) && false == $params['wrap'] ? false : true;

    if (isset($params['package'])) {
        $iaCore = iaCore::instance();
        $urlPrefix = $iaCore->modulesData[$params['package']]['url'];
    } elseif (defined('IA_MODULE_URL')) {
        $urlPrefix = IA_MODULE_URL;
    }

    if ($categories) {
        $out = '';

        if ($wrap) {
            $out .= '<div class="categories">';
        }

        $out .= '<table class="columns"><tbody><tr>';

        $i = 0;
        $total = count($categories);
        foreach ($categories as $category) {
            $i++;
            if ($rows) {
                $out .= ((($i % $rows == 1) && ($rows > 1)) || ($i == 1)) ? '<td style="width:' . ceil(100 / $columns) . '%;">' : '';
            } else {
                $out .= '<td style="width:' . floor(100 / $columns) . '%;">';
            }
            $cat_url = $urlPrefix . rtrim($category['title_alias'], '/') . '/';
            // display content part for the cell
            $icon = '';

            if ($displayIcon && !empty($category['icon'])) {
                $icon = '<div class="icon"><img src="' . IA_URL . 'uploads/' . $category['icon'] . '" alt=""></div>';
            }

            $num_listings = '';
            if ($show_amount) {
                $num_listings = ' <span class="count">(' . number_format($category['num'], 0, '', ' ') . ')</span>';
            }
            $out .= '<div class="category-block">' . $icon . '<p class="title"><a href="' . $cat_url . '">' . $category['title'] . '</a>' . $num_listings . '</p>';
            if (isset($category['subcategories'])) {
                $out .= '<div class="subcategories">';
                foreach ($category['subcategories'] as $k => $subcategory) {
                    $out .= '<a href="' . $urlPrefix . $subcategory['title_alias'] . '">' . $subcategory['title'] . '</a>, ';
                }
                $out = trim($out, " ,");
                $out .= '</div>';
            }
            $out .= '</div>';

            if ($rows) {
                $out .= ((($i % $rows == 0) && ($rows > 1)) || ($i == $total)) ? '</td>' : '';
            } else {
                $out .= '</td>';
                if ($i % $columns == 0) {
                    $out .= "</tr><tr>";
                }
            }
        }
        $out .= '<td></td></tr></tbody></table>';

        if ($wrap) {
            $out .= '</div>';
        }

        echo $out;
    }
}
