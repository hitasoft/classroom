<?php
/*
 * loginform.php : login form popup
 *      
 */
session_name('connect');
session_start();
?>
<div class="closeBtn"><a class="close"></a></div>
<div id="loginForm" class="dlgForm">
	<h2>Sign in to Educator-Classroom</h2>
	<form action='http://classroom.educator.com/account/login.php' method='post'>
	<table>
		<tr class="dataRow">
			<th><label for="amember_login">Username:</label></th>
			<td><input type="text" id="amember_login" name="amember_login" value="" placeholder='Your username'>
				<a>forgot?</a>
				<div class="calloutContent" id="forgotU">
					<div class="closeBtn"></div>
					<p class="title">Forgot your username?</p>
					<p>Enter your email address and we'll help you recover your username.</p>
					<span class="button">
						<a href="/account/login.php?o=recover">Get started</a>
					</span>
				</div>
			</td>
		</tr>
		<tr class="spaceRow"><td colspan="2"></td></tr>
		<tr class="dataRow">
			<th><label for="amember_pass">Password:</label></th>
			<td><input type='password' name='amember_pass' id='amember_pass' placeholder='Your password'>
				<a>forgot?</a>
				<div class="calloutContent" id="forgotP">
					<div class="closeBtn"></div>
					<p class="title">Forgot your password?</p>
					<p>Enter your email address and we'll help you recover your password.</p>
					<span class="button">
						<a href="/account/login.php?o=forget">Get started</a>
					</span>
				</div>
			</td>
		</tr>
		<tr class="spaceRow"><td colspan="2"></td></tr>
		<tr class='actionRow'>
			<th></th>
			<td><input type='submit' value='Login' id='amember_submit'></td>
		</tr>
	</table>
	</form>
</div>
