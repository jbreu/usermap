<?php
/**
*
* @package phpBB Extension - tas2580 Social Media Buttons
* @copyright (c) 2014 tas2580 (https://tas2580.net)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace tas2580\usermap\controller;

class main extends \tas2580\usermap\includes\class_usermap
{
	/** @var \phpbb\auth\auth */
	protected $auth;
	/** @var \phpbb\config\config */
	protected $config;
	/** @var \phpbb\db\driver\driver */
	protected $db;
	/** @var \phpbb\controller\helper */
	protected $helper;
	/** @var \phpbb\paginationr */
	protected $paginationr;
	/** @var \phpbb\path_helper */
	protected $path_helper;
	/** @var string */
	protected $phpbb_extension_manager;
	/** @var \phpbb\request\request */
	protected $request;
	/** @var \phpbb\user */
	protected $user;
	/** @var \phpbb\template\template */
	protected $template;
	/** @var string phpbb_root_path */
	protected $phpbb_root_path;
	/** @var string php_ext */
	protected $php_ext;

	/**
	* Constructor
	*
	* @param \phpbb\auth\auth			$auth		Auth object
	* @param \phpbb\controller\helper	$helper
	* @param \phpbb\template\template	$template
	*/
	public function __construct(\phpbb\auth\auth $auth, \phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\controller\helper $helper, \phpbb\pagination $pagination, \phpbb\path_helper $path_helper, \phpbb\request\request $request, $phpbb_extension_manager, \phpbb\user $user, \phpbb\template\template $template, $phpbb_root_path, $php_ext)
	{
		$this->auth = $auth;
		$this->config = $config;
		$this->db = $db;
		$this->helper = $helper;
		$this->pagination = $pagination;
		$this->path_helper = $path_helper;
		$this->phpbb_extension_manager = $phpbb_extension_manager;
		$this->request = $request;
		$this->user = $user;
		$this->template = $template;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $php_ext;
		$this->user->add_lang_ext('tas2580/usermap', 'controller');
	}

	public function index()
	{
		if (!$this->auth->acl_get('u_usermap_view'))
		{
			trigger_error('NOT_AUTHORISED');
		}

		$this->template->assign_block_vars('navlinks', array(
			'FORUM_NAME'		=> $this->user->lang('USERMAP_TITLE'),
			'U_VIEW_FORUM'	=> $this->helper->route('tas2580_usermap_index', array()),
		));

		$sql = 'SELECT group_id, group_name, group_usermap_marker, group_type, group_colour
			FROM ' . GROUPS_TABLE . "
			WHERE group_usermap_marker != ''
			ORDER BY group_name";
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$group_name = ($row['group_type'] == GROUP_SPECIAL) ? $this->user->lang('G_' . $row['group_name']) : $row['group_name'];
			$colour_text = ($row['group_colour']) ? ' style="color:#' . $row['group_colour'] . '"' : '';
			if ($row['group_name'] == 'BOTS' || ($this->user->data['user_id'] != ANONYMOUS && !$this->auth->acl_get('u_viewprofile')))
			{
				$legend = '<span' . $colour_text . '>' . $group_name . '</span>';
			}
			else
			{
				$legend = '<a' . $colour_text . ' href="' . append_sid("{$this->phpbb_root_path}memberlist.{$this->php_ext}", 'mode=group&amp;g=' . $row['group_id']) . '">' . $group_name . '</a>';
			}
			$this->template->assign_block_vars('group_list', array(
				'GROUP_ID'		=> $row['group_id'],
				'GROUP_NAME'		=> $legend,
				'ALT'				=> $group_name,
				'MARKER'			=> $row['group_usermap_marker'],
			));
		}

		include($this->phpbb_root_path . 'includes/functions_display.' . $this->php_ext);

		$my_lon = $this->user->data['user_usermap_lon'];
		$my_lat = $this->user->data['user_usermap_lat'];

		$sql = 'SELECT user_id, username, user_colour, group_id, user_usermap_lon, user_usermap_lat
			FROM ' . USERS_TABLE . "
			WHERE user_usermap_lon != ''
				AND user_usermap_lat != ''";
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$distance = $this->get_distance($my_lon, $my_lat, $row['user_usermap_lon'], $row['user_usermap_lat']);
			$this->template->assign_block_vars('user_list', array(
				'USER_ID'			=> $row['user_id'],
				'USERNAME'		=> get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
				'USERNAME_SIMPLE'	=> get_username_string('username', $row['user_id'], $row['username'], $row['user_colour']),
				'LON'				=> $row['user_usermap_lon'],
				'LAT'				=> $row['user_usermap_lat'],
				'GROUP_ID'		=> $row['group_id'],
				'DISTANCE'		=> $distance,
			));
		}

		$this->template->assign_vars(array(
			'USERMAP_CONTROLS'	=> 'true',
			'S_IN_USERMAP'		=> true,
			'USERMAP_LON'		=> empty($this->config['tas2580_usermap_lon']) ? 0 : $this->config['tas2580_usermap_lon'],
			'USERMAP_LAT'			=> empty($this->config['tas2580_usermap_lat']) ? 0 : $this->config['tas2580_usermap_lat'],
			'USERMAP_ZOOM'		=> (int) $this->config['tas2580_usermap_zoom'],
			'MARKER_PATH'		=> $this->path_helper->update_web_root_path($this->phpbb_extension_manager->get_extension_path('tas2580/usermap', true) . 'marker'),
			'A_USERMAP_ADD'		=> $this->auth->acl_get('u_usermap_add'),
			'U_SET_POSITON'		=> $this->helper->route('tas2580_usermap_position', array()),
			'MAP_TYPE'			=> $this->config['tas2580_usermap_map_type'],
			'GOOGLE_API_KEY'		=> $this->config['tas2580_usermap_google_api_key'],
			'A_USERMAP_SEARCH'	=> true,
			'U_USERMAP_SEARCH'	=> $this->helper->route('tas2580_usermap_search', array()),
			'L_MENU_SEARCH'		=> $this->user->lang('MENU_SEARCH', $this->config['tas2580_usermap_search_distance'])
		));
		return $this->helper->render('usermap_body.html', $this->user->lang('USERMAP_TITLE'));
	}

	public function search($start = 1)
	{

		if (!$this->auth->acl_get('u_usermap_search'))
		{
		//	trigger_error('NOT_AUTHORISED');
		}

		$this->template->assign_block_vars('navlinks', array(
			'FORUM_NAME'		=> $this->user->lang('USERMAP_TITLE'),
			'U_VIEW_FORUM'	=> $this->helper->route('tas2580_usermap_index', array()),
		));

		$lon = substr($this->request->variable('lon', ''), 0, 10);
		$lat = substr($this->request->variable('lat', ''), 0, 10);
		$dst = $this->request->variable('dst', $this->config['tas2580_usermap_search_distance']);

		$alpha = 180 * $dst / (6378137 / 1000 * 3.14159);
		$min_lon = $this->db->sql_escape($lon - $alpha);
		$max_lon = $this->db->sql_escape($lon + $alpha);
		$min_lat = $this->db->sql_escape($lat - $alpha);
		$max_lat = $this->db->sql_escape($lat + $alpha);

		$where = " WHERE ( user_usermap_lon >= '$min_lon' AND user_usermap_lon <= '$max_lon') AND ( user_usermap_lat >= '$min_lat' AND user_usermap_lat<= '$max_lat')";
		$limit = (int) $this->config['topics_per_page'];

		$sql = 'SELECT COUNT(user_id) AS num_users
			FROM ' . USERS_TABLE . $where;
		$result = $this->db->sql_query($sql);
		$total_users = (int) $this->db->sql_fetchfield('num_users');
		$this->db->sql_freeresult($result);

		$sql = 'SELECT user_id, username, user_colour, user_regdate, user_posts, group_id, user_usermap_lon, user_usermap_lat
			FROM ' . USERS_TABLE . $where;
		$result = $this->db->sql_query_limit($sql, $limit, ($start -1)  * $limit);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$distance = $this->get_distance($lon, $lat, $row['user_usermap_lon'], $row['user_usermap_lat']);
			$this->template->assign_block_vars('memberrow', array(
				'USER_ID'			=> $row['user_id'],
				'USERNAME'		=> get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
				'JOINED'			=> $this->user->format_date($row['user_regdate']),
				'POSTS'			=> $row['user_posts'],
				'GROUP_ID'		=> $row['group_id'],
				'DISTANCE'		=> $distance,
			));
		}

		$this->pagination->generate_template_pagination(array(
			'routes' => array(
				'tas2580_usermap_search',
				'tas2580_usermap_search_page',
			),
			'params' => array(
			),
		), 'pagination', 'start', $total_users, $limit, ($start - 1)  * $limit);

		$this->template->assign_vars(array(
			'TOTAL_USERS'		=> $this->user->lang('TOTAL_USERS', (int) $total_users),
			'L_SEARCH_EXPLAIN'		=> $this->user->lang('SEARCH_EXPLAIN', $dst, $lon, $lat),
		));

		return $this->helper->render('usermap_search.html', $this->user->lang('USERMAP_SEARCH'));
	}


	public function position()
	{
		if (!$this->auth->acl_get('u_usermap_add'))
		{
			trigger_error('NOT_AUTHORISED');
		}

		$lon = substr($this->request->variable('lon', ''), 0, 10);
		$lat = substr($this->request->variable('lat', ''), 0, 10);

		if (confirm_box(true))
		{
			$data = array(
				'user_usermap_lon'			=> $lon,
				'user_usermap_lat'			=> $lat,
			);

			if (!function_exists('validate_data'))
			{
				include($this->phpbb_root_path . 'includes/functions_user.' . $this->php_ext);
			}
			$error = validate_data($data, array(
				'user_usermap_lon'			=> array(
					array('string', true, 5, 10)
				),
				'user_usermap_lat'			=> array(
					array('string', true, 5, 10)
				),
			));
			$error = array_map(array($this->user, 'lang'), $error);
			if (sizeof($error))
			{
				trigger_error(implode('<br>', $error) . '<br><br><a href="' . $this->helper->route('tas2580_usermap_index', array()) . '">' . $this->user->lang('BACK_TO_USERMAP') . '</a>');
			}
			$sql = 'UPDATE ' . USERS_TABLE . '
				SET ' . $this->db->sql_build_array('UPDATE', $data) . '
				WHERE user_id = ' . (int) $this->user->data['user_id'] ;

			$this->db->sql_query($sql);

			redirect($this->helper->route('tas2580_usermap_index', array()));
		}
		else
		{
			confirm_box(false, $this->user->lang('CONFIRM_COORDINATES_SET', $lon, $lat), build_hidden_fields(array(
				'lon'		=> $lon,
				'lat'		=> $lat))
			);
		}
		return $this->index();
	}
}
