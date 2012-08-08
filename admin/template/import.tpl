{combine_css path=$PICASA_WA_PATH|@cat:"admin/template/style.css"}

<div class="titrePage">
	<h2>Google2Piwigo</h2>
</div>

{* <!-- LOGIN --> *}
{if $ACTION == 'login'}
<p><input type="submit" onClick="javascript:window.location.href ='{$picasa_login}';" value="{'Login'|@translate}"></p>

{* <!-- MAIN MENU --> *}
{elseif $ACTION == 'main'}
{footer_script}{literal}
jQuery('input[type="submit"]').click(function() {
  window.location.href = $(this).attr("data");
});
jQuery('.load').click(function() {
  $("#loader_import").fadeIn();
});
{/literal}{/footer_script}

<p>
  <input type="submit" data="{$logout_url}" value="{'Logout'|@translate}">
</p>
<br>
<p>
  <input type="submit" data="{$list_albums_url}" class="load" value="{'List my albums'|@translate}">
  <input type="submit" data="{$import_all_url}" class="load" value="{'Import all my pictures'|@translate}">
  <br>
  <span id="loader_import" style="display:none;"><img src="admin/themes/default/images/ajax-loader.gif"> <i>{'Processing...'|@translate}</i></span>
</p>

{* <!-- ALBUMS LIST --> *}
{elseif $ACTION == 'list_albums'}
{footer_script}{literal}
jQuery('.load').click(function() {
  $("#loader_import").fadeIn();
});
{/literal}{/footer_script}

<h3>{'%d albums'|@translate|@sprintf:$total_albums}</h3>
<ul id="albumsList">
{foreach from=$albums item=album}
  <li>
    <b><a href="{$album.U_LIST}" class="load">{$album.title}</a></b> <i>{'(%d photos)'|@translate|@sprintf:$album.photos}</i> 
    {if $album.description}- {$album.description|@truncate:100}{/if}
  </li>
{/foreach}
</ul>
<span id="loader_import" style="display:none;"><img src="admin/themes/default/images/ajax-loader.gif"> <i>{'Processing...'|@translate}</i></span>

{* <!-- PHOTOS LIST --> *}
{elseif $ACTION == 'list_photos'}
{include file=$PICASA_WA_ABS_PATH|@cat:'admin/template/import.list_photos.tpl'}

{* <!-- IMPORT ALL --> *}
{elseif $ACTION == 'list_all'}
{include file=$PICASA_WA_ABS_PATH|@cat:'admin/template/import.list_all.tpl'}

{/if}