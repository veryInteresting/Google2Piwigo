{combine_css path=$PICASA_WA_PATH|cat:'admin/template/style.css'}

<div class="titrePage">
	<h2>Google2Piwigo</h2>
</div>

<form method="post" action="" class="properties">
<fieldset>
  <legend>{'Google project keys'|translate}</legend>

  <ul>
    <li>
      <label>
        <span class="property">Client ID</span>
        <input type="text" name="api_key" value="{$google2piwigo.api_key}" size="40">
      </label>
    </li>

    <li>
      <label>
        <span class="property">Client secret</span>
        <input type="text" name="secret_key" value="{$google2piwigo.secret_key}" size="20">
      </label>
    </li>
  </ul>
</fieldset>

<p><input type="submit" name="save_config" value="{'Save Settings'|translate}"></p>

<fieldset>
  <legend>{'How do I get my Google project API key?'|translate}</legend>

  <p><b>{'Callback URL'|translate} :</b> <span style="font-family:monospace;font-size:14px;">{$PICASA_WA_CALLBACK}</span></p>
  {$PICASA_WA_HELP_CONTENT}
</fieldset>

</form>