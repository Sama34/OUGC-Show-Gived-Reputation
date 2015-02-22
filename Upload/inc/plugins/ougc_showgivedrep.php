<?php

/***************************************************************************
 *
 *	OUGC Signature Control plugin (/inc/plugins/ougc_showgivedrep.php)
 *	Author: Omar Gonzalez
 *	Copyright: © 2012-2014 Omar Gonzalez
 *
 *	Website: http://omarg.me
 *
 *	Allow users to see their given reputation from their reputation pages.
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
defined('IN_MYBB') or die('Direct initialization of this file is not allowed.');

// Run/Add Hooks
if(!defined('IN_ADMINCP') && defined('THIS_SCRIPT') && THIS_SCRIPT == 'reputation.php')
{
	global $templatelist;

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

// Plugin API
function ougc_showgivedrep_info()
{
	global $lang;
	ougc_showgivedrep_load_language();

	return array(
		'name'			=> 'OUGC Show Gived Reputation',
		'description'	=> $lang->ougc_showgivedrep_desc,
		'website'		=> 'http://omarg.me',
		'author'		=> 'Omar G.',
		'authorsite'	=> 'http://omarg.me',
		'version'		=> '1.8',
		'versioncode'	=> 1800,
		'compatibility'	=> '18*'
	);
}

// _activate() routine
function ougc_showgivedrep_activate()
{
	global $cache;
	ougc_showgivedrep_deactivate();

	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets('reputation', '#'.preg_quote('show_negative}</option>').'#', 'show_negative}</option>{$ougc_showgivedrep}');

	// Insert/update version into cache
	$plugins = $cache->read('ougc_plugins');
	if(!$plugins)
	{
		$plugins = array();
	}

	$info = ougc_showgivedrep_info();

	if(!isset($plugins['showgivedrep']))
	{
		$plugins['showgivedrep'] = $info['versioncode'];
	}

	/*~*~* RUN UPDATES START *~*~*/

	/*~*~* RUN UPDATES END *~*~*/

	$plugins['showgivedrep'] = $info['versioncode'];
	$cache->update('ougc_plugins', $plugins);
}

// _deactivate() routine
function ougc_showgivedrep_deactivate()
{
	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets('reputation', '#'.preg_quote('{$ougc_showgivedrep}').'#', '', 0);
}

// _is_installed() routine
function ougc_showgivedrep_is_installed()
{
	global $cache;

	$plugins = (array)$cache->read('ougc_plugins');

	return isset($plugins['showgivedrep']);
}

// _uninstall() routine
function ougc_showgivedrep_uninstall($hard=true)
{
	global $cache;

	// Delete version from cache
	$plugins = (array)$cache->read('ougc_plugins');

	if(isset($plugins['showgivedrep']))
	{
		unset($plugins['showgivedrep']);
	}

	if(!empty($plugins))
	{
		$cache->update('ougc_plugins', $plugins);
	}
	else
	{
		$cache->delete('ougc_plugins');
	}
}

// Load language if exists
function ougc_showgivedrep_load_language()
{
	global $lang;

	isset($lang->ougc_showgivedrep) or $lang->load('ougc_showgivedrep', false, true);

	isset($lang->ougc_showgivedrep_desc) or $lang->ougc_showgivedrep_desc = 'Allow users to see their given reputation from their reputation pages.';
}

// Dark magic
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
					$selfrepdone2 = true;$string2 = $string;
					$string = str_replace(array(\'r.uid\', \'(u.uid=r.adduid)\'),  array(\'r.adduid\', \'(u.uid=r.uid)\'), preg_replace(\'#r\.uid\=\\\'([0-9]+)\\\'#i\',  \'r.adduid=\\\''.($mybb->user['uid']).'\\\'\', $string));
				}
				return parent::query($string, $hide_errors, $write_query);
			}
		');

		global $plugins;

		$plugins->add_hook('reputation_end', create_function('', 'global $multipage; if($multipage) { $multipage = str_replace(\'show=all\', \'show=self\', $multipage); }'));

		$plugins->add_hook('reputation_vote', create_function('', 'global $postrep_given, $reputation_vote, $lang, $link, $user, $thread_link; if($reputation_vote[\'pid\']) { $postrep_given = $lang->sprintf($lang->postrep_given, $link, $reputation_vote[\'username\'], $thread_link); }'));
	}

	isset($templates->cache['ougc_showgivedrep']) or $templates->cache['ougc_showgivedrep'] = '<option value="self"{$show_selected[\'self\']}>{$lang->ougc_showgivedrep_self}</option>';

	eval('$ougc_showgivedrep = "'.$templates->get('ougc_showgivedrep').'";');
}

// control_object by Zinga Burga from MyBBHacks ( mybbhacks.zingaburga.com ), 1.62
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
				{
					$k = substr($k, $p+1);
				}
				$vars[$k] = $v;
			}
			if(!empty($vars))
			{
				$code .= '
					function ___setvars(&$a) {
						foreach($a as $k => &$v)
							$this->$k = $v;
					}
				';
			}
			eval('class '.$newname.' extends '.$classname.' {'.$code.'}');
			$obj = unserialize('O:'.strlen($newname).':"'.$newname.'":'.substr($objserial, $checkstr_len));
			if(!empty($vars))
			{
				$obj->___setvars($vars);
			}
		}
		// else not a valid object or PHP serialize has changed
	}
}