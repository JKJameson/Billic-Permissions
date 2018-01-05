<?php
class Permissions {
	public $settings = array(
		'name' => 'Permissions',
		'admin_menu_category' => 'Settings',
		'admin_menu_name' => 'Permissions',
		'admin_menu_icon' => '<i class="icon-shield"></i>',
		'description' => 'Give permissions to users to access restricted areas of Billic.',
	);
	function admin_area() {
		global $billic, $db;
		$billic->set_title('Admin/Permissions');
		echo '<h1>Permissions</h1>';
		if (isset($_POST['add_email'])) {
			$user = $db->q('SELECT * FROM `users` WHERE `email` = ?', $_POST['add_email']);
			$user = $user[0];
			if (empty($user)) {
				$billic->error('User not found');
			} else if ($id == $billic->user['id']) {
				$billic->error('You can\'t add permissions to yourself');
			} else if ($billic->user_has_permission($user, 'superadmin')) {
				$billic->error('User is already a super admin');
			} else if ($billic->user_has_permission($user, 'admin')) {
				$billic->error('User is already an admin');
			} else {
				$db->q('UPDATE `users` SET `permissions` = ? WHERE `id` = ?', trim('+admin' . PHP_EOL . $user['permissions']) , $user['id']);
				$billic->status = 'added';
			}
		} else if (isset($_POST['remove'])) {
			foreach ($_POST['remove'] as $id => $txt) {
				if ($id == $billic->user['id']) {
					die('You can\'t remove permissions from yourself');
				}
				$db->q('UPDATE `users` SET `permissions` = ? WHERE `id` = ?', '', $id);
				$billic->status = 'deleted';
			}
		} else if (isset($_POST['p'])) {
			foreach ($_POST['p'] as $id => $perms) {
				if ($id == $billic->user['id']) {
					die('You can\'t change your own permissions');
				}
				$permissions = '';
				foreach ($perms as $perm => $v) {
					$permissions.= '+' . html_entity_decode($perm, ENT_QUOTES, 'ISO-8859-1') . PHP_EOL;
				}
				$permissions = trim($permissions);
				$db->q('UPDATE `users` SET `permissions` = ? WHERE `id` = ?', $permissions, $id);
				$billic->status = 'updated';
			}
		}
		$billic->show_errors();
		echo '<form method="POST"><table class="table table-striped"><tr><th colspan="2">Give Admin Permissions to a User</th></tr><tr><td>Email</td><td><input type="text" class="form-control" name="add_email"></td></tr><tr><td colspan="2" align="center"><input type="submit" class="btn btn-success" value="Promote User to Admin &raquo;"></td></tr></table></form><br><br>';
		$permissions = array();
		$permissions[] = 'Dashboard';
		$modules = $db->q('SELECT `id`, `permissions` FROM `modules` WHERE `methods` LIKE \'%|admin_area|%\' OR `methods` LIKE \'%|%_submodule|%\' OR `permissions` != \'\' ORDER BY `id` ASC');
		foreach ($modules as $module) {
			$permissions[] = $module['id'];
			$sub = json_decode($module['permissions'], true);
			if (is_array($sub)) {
				foreach ($sub as $s) {
					$permissions[] = $s;
				}
			}
		}
		sort($permissions);
		$users = $db->q('SELECT `id`, `firstname`, `lastname`, `email`, `permissions` FROM `users` WHERE `permissions` != ?', '');
		echo '<table class="table table-striped"><tr><th width="100">User</th><th>Permissions</th></tr>';
		foreach ($users as $user) {
			$gravatar_width = 60;
			echo '<tr><td align="center" valign="top"><img src="' . $billic->avatar($user['email'], $gravatar_width) . '" width="' . $gravatar_width . '" height="' . $gravatar_width . '"><br><br>' . $user['firstname'] . ' ' . $user['lastname'] . '</td><td valign="top"><form method="POST"><input type="hidden" name="user" value="' . safe($user['email']) . '">';
			$isSuperAdmin = $billic->user_has_permission($user, 'superadmin');
			echo '<input type="checkbox" name="p[' . $user['id'] . '][superadmin]" ' . ($isSuperAdmin ? ' checked' : '') . '> User is a Super Admin (enables access to ALL permissions)<br><br>';
			echo '<div id="superAdminPermissions-' . $user['id'] . '"' . ($isSuperAdmin ? ' style="display:none"' : '') . '>';
			$isAdmin = $billic->user_has_permission($user, 'admin');
			echo '<input type="checkbox" name="p[' . $user['id'] . '][admin]" ' . ($isAdmin ? ' checked' : '') . '> User is an Admin (enables access to the Admin Area)<br>';
			$chunks = array_chunk($permissions, ceil(count($permissions) / 3));
			echo '<div id="adminPermissions-' . $user['id'] . '"' . ($isAdmin ? : ' style="display:none"') . '>';
			foreach ($chunks as $chunk) {
				echo '<div style="width:30%;float:left;margin: 15px"><table class="table table-striped"';
				echo '<tr><th>Granted&nbsp;Admin&nbsp;Permissions</th></tr>';
				foreach ($chunk as $permission) {
					$hasPermission = $billic->user_has_permission($user, $permission);
					$parts = explode('_', $permission);
					$text = $parts[0];
					unset($parts[0]);
					if (!empty($parts)) {
						$text.= ' &raquo; ' . ucwords(implode(' ', $parts));
					}
					echo '<tr><td><input type="checkbox" name="p[' . $user['id'] . '][' . safe($permission) . ']" value="1"' . ($hasPermission ? ' checked' : '') . '> ' . $text . '</td></tr>';
				}
				echo '</table></div>';
			}
			echo '</div>'; // adminPermissions
			echo '</div>'; // superAdminPermissions
			echo '<div style="clear:both"></div><input type="submit" class="btn btn-danger" name="remove[' . $user['id'] . ']" value="Remove All Permissions &raquo;" style="float:right"><input type="submit" class="btn btn-success" value="Update Permissions &raquo;"></form></td></tr>';
			echo '<script>
addLoadEvent(function() {
    $(\'input[type="checkbox"][name="p[' . $user['id'] . '][superadmin]"]\').change(function() {
        if(this.checked) {
            $("#superAdminPermissions-' . $user['id'] . '").hide();
        } else {
            $("#superAdminPermissions-' . $user['id'] . '").show();
        }
        });
    $(\'input[type="checkbox"][name="p[' . $user['id'] . '][admin]"]\').change(function() {
        if(this.checked) {
            $("#adminPermissions-' . $user['id'] . '").show();
        } else {
            $("#adminPermissions-' . $user['id'] . '").hide();
        }
    });
});</script>
';
		}
		echo '</table>';
	}
}
