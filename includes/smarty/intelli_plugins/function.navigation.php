<?php

function smarty_function_navigation ($params, &$smarty)
{
	if ($params['aTotal'] && $params['aTotal'] > $params['aItemsPerPage'])
	{
		if (!isset($params['aNumPageItems']))
		{
			$params['aNumPageItems'] = 5;
		}

		$replace_exp = '/(\?|&|_)*[a-zA-Z=-]*{page}(.html)*/';
		$replace_exp_page = '/(\?|&|_)(.*?)({page})/';

		$params['aTruncateParam'] = isset($params['aTruncateParam']) ? $params['aTruncateParam'] : 0;

		$num_pages = ceil($params['aTotal'] / $params['aItemsPerPage']);
		$current_page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
		$current_page = min($current_page, $num_pages);

		$left_offset = ceil($params['aNumPageItems'] / 2) - 1;

		$first = $current_page - $left_offset;
		$first = ($first < 1) ? 1 : $first;

		$last = $first + $params['aNumPageItems'] - 1;
		$last = min($last, $num_pages);

		$first = $last - $params['aNumPageItems'] + 1;
		$first = ($first < 1) ? 1 : $first;

		$pages = range($first, $last);

		$out = '<div class="pagination"><ul>';

		foreach ($pages as $page)
		{
			if ($current_page == $page)
			{
				$out .= '<li><span>' . iaLanguage::get('page') . ' ' . $page . ' / ' . $num_pages . '</span></li>';
				break;
			}
		}

		// the first and previous items menu
		if ($current_page > 1)
		{
			$prev = $current_page - 1;

			$first_url = (1 == $params['aTruncateParam']) ? preg_replace($replace_exp_page, '', $params['aTemplate']) : preg_replace($replace_exp, '', $params['aTemplate']);
			$previous_url = (1 == $prev) ? preg_replace($replace_exp, '', $params['aTemplate']) : str_replace('{page}', $prev, $params['aTemplate']);

			$out .= '<li><a href="' . $first_url . '" title="' . iaLanguage::get('first') . '">&#171;</a></li>';
			$out .= '<li><a href="' . $previous_url . '" title="' . iaLanguage::get('previous') . '">&lt;</a></li>';
		}

		// the pages items
		foreach ($pages as $page)
		{
			if ($current_page == $page)
			{
				$out .= '<li class="active"><span>' . $page . '</span></li>';
			}
			else
			{
				if(1 == $page)
				{
					$page_url = (1 == $params['aTruncateParam']) ? preg_replace($replace_exp_page, '', $params['aTemplate']) : preg_replace($replace_exp, '', $params['aTemplate']);
				}
				else
				{
					$page_url = str_replace('{page}', $page, $params['aTemplate']);
				}

				$out .= '<li><a href="' . $page_url . '">' . $page . '</a></li>';
			}
		}

		// the next and last items menu
		if ($current_page < $num_pages)
		{
			$next = $current_page + 1;

			$next_url = str_replace('{page}', $next, $params['aTemplate']);
			$last_url = str_replace('{page}', $num_pages, $params['aTemplate']);

			$out .= '<li><a href="' . $next_url . '" title="' . iaLanguage::get('next') . '">&gt;</a></li>';
			$out .= '<li><a href="' . $last_url . '" title="' . iaLanguage::get('last') . '">&#187;</a></li>';
		}

		$out .= '</ul></div>';

		return $out;
	}
}