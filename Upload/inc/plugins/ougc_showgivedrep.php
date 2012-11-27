<?php

/***************************************************************************
 *
 *   OUGC Show Gived Reputation plugin (/inc/plugins/ougc_showgivedrep.php)
 *	 Author: Omar Gonzalez
 *   Copyright: © 2012 Omar Gonzalez
 *   
 *   Website: http://community.mybb.com/user-25096.html
 *
 *   Allow users to see their given reputation from their reputation pages.
 *
 ***************************************************************************
 
****************************************************************************
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/

// Die if IN_MYBB is not defined, for security reasons.
defined('IN_MYBB') or die('This file cannot be accessed directly.');

// Run the ACP hooks.
if(!defined('IN_ADMINCP') && defined('THIS_SCRIPT') && THIS_SCRIPT == 'reputation.php')
{
	global $plugins, $templatelist;

	$plugins->add_hook('reputation_start', 'ougc_showgivedrep_variable');

	if(isset($templatelist))
	{
		$templatelist .= ',';
	}
	else
	{
		$templatelist = '';
	}

	$templatelist .= 'ougc_showgivedrep';
}

// Array of information about the plugin.
function ougc_showgivedrep_info()
{
	global $lang;
	isset($lang->ougc_showgivedrep) or $lang->load('ougc_showgivedrep', false, true);

	return array(
		'name'			=> 'OUGC Show Gived Reputation',
		'description'	=> (isset($lang->ougc_showgivedrep_d) ? $lang->ougc_showgivedrep_d : 'Allow users to see their given reputation from their reputation pages.'),
		'website'		=> 'http://mods.mybb.com/profile/25096',
		'author'		=> 'Omar Gonzalez',
		'authorsite'	=> 'http://community.mybb.com/user-25096.html',
		'version'		=> '1.0',
		'guid' 			=> '',
		'compatibility' => '16*'
	);
}

// This function runs when the plugin is activated.
function ougc_showgivedrep_activate()
{
	ougc_showgivedrep_deactivate();

	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets('reputation', '#'.preg_quote('show_negative}</option>').'#', 'show_negative}</option>{$ougc_showgivedrep}');
}

//Deactivate the plugin.
function ougc_showgivedrep_deactivate()
{
	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets('reputation', '#'.preg_quote('{$ougc_showgivedrep}').'#', '', 0);
}

function ougc_showgivedrep_variable()
{
	global $mybb;

	if($mybb->input['action'])
	{
		return;
	}

	global $lang, $templates, $ougc_showgivedrep;
	isset($lang->ougc_showgivedrep) or $lang->load('ougc_showgivedrep', false, true);
	isset($lang->ougc_showgivedrep_self) or $lang->ougc_showgivedrep_self = 'Show: All given';
	

	$show_selected['self'] = '';
	if($mybb->input['show'] == 'self')
	{
		isset($lang->ougc_showgivedrep_title) or $lang->ougc_showgivedrep_title = 'Gived Reputations';
		$lang->comments = $lang->ougc_showgivedrep_title;
		$show_selected['self'] = ' selected="selected"';
		control_object($GLOBALS['db'], '
			function query($string, $hide_errors=0, $write_query=0)
			{
				static $selfrepdone = false;
				if(!$selfrepdone && !$write_query && strpos($string, \'COUNT(r.rid) AS reputation_count\'))
				{
					$selfrepdone = true;
					$string = preg_replace(\'#r\.uid\=\\\'([0-9]+)\\\'#i\',  \'r.adduid=\\\''.($mybb->user['uid']).'\\\'\', $string);
				}
				static $selfrepdone2 = false;
				if(!$selfrepdone2 && !$write_query && strpos($string, \'r.uid\'))
				{
					$selfrepdone2 = true;
					$string = str_replace(array(\'r.uid\', \'(u.uid=r.adduid)\'),  array(\'r.adduid\', \'(u.uid=r.uid)\'), preg_replace(\'#r\.uid\=\\\'([0-9]+)\\\'#i\',  \'r.adduid=\\\''.($mybb->user['uid']).'\\\'\', $string));
					_dump($string);
				}
				return parent::query($string, $hide_errors, $write_query);
			}
		');

		global $plugins;

		$plugins->add_hook('reputation_end', create_function('', 'global $multipage; if($multipage) { $multipage = str_replace(\'show=all\', \'show=self\', $multipage); }'));
	}

	if(empty($templates->cache['ougc_showgivedrep']))
	{
		$templates->cache['ougc_showgivedrep'] = '<option value="self"{$show_selected[\'self\']}>{$lang->ougc_showgivedrep_self}</option>';
	}

	eval('$ougc_showgivedrep = "'.$templates->get('ougc_showgivedrep').'";');
}

// Control object written by Zinga Burga / Yumi from ( http://mybbhacks.zingaburga.com/ )
if(!function_exists('control_object'))
{
	function control_object(&$obj, $code)
	{
		static $cnt = 0;
		$newname = '_objcont_'.(++$cnt);
		$objserial = serialize($obj);
		$classname = get_class($obj);
		$checkstr = 'O:'.strlen($classname).':"'.$classname.'":';
		$checkstr_len = strlen($checkstr);
		if(substr($objserial, 0, $checkstr_len) == $checkstr)
		{
			$vars = array();
			// grab resources/object etc, stripping scope info from keys
			foreach((array)$obj as $k => $v)
			{
				if($p = strrpos($k, "\0"))
					$k = substr($k, $p+1);
				$vars[$k] = $v;
			}
			if(!empty($vars))
				$code .= '
					function ___setvars(&$a) {
						foreach($a as $k => &$v)
							$this->$k = $v;
					}
				';
			eval('class '.$newname.' extends '.$classname.' {'.$code.'}');
			$obj = unserialize('O:'.strlen($newname).':"'.$newname.'":'.substr($objserial, $checkstr_len));
			if(!empty($vars))
				$obj->___setvars($vars);
		}
		// else not a valid object or PHP serialize has changed
	}
}