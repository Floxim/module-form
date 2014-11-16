{template id="form" test="!($ instanceof \Floxim\Form\Form)"}
    {if $form instanceof \Floxim\Form\Form}
        {apply form with $form /}
    {/if}
{/template}

<form 
    fx:template="form" 
    fx:if="$ instanceof \Floxim\Form\Form"
    action="{$action}" 
    method="{$method}" 
    id="{$.getId()}"
    class="fx_form {$class} {if $is_sent} fx_form_sent{/if}{if $.ajax} fx_form_ajax{/if}"
    enctype="multipart/form-data">
    {js}
        FX_JQUERY_PATH as jquery
        form.js
    {/js}
    {if $.skin == 'default'}
        {css}
            form_default.less
        {/css}
    {/if}
    <input type="hidden" name="{$.getId()}_sent" value="1" />
    {$_.content}
        {apply messages /}
        {apply errors /}
        {$fields.find('type', 'submit', '!=') || :row /}
        <div class="fx_submit_row">
            {$fields.find('type', 'submit') || :input_block /}
        </div>
    {/$}
</form>

<form 
    fx:template="form[$is_finished]" 
    class="fx_form fx_form_sent fx_form_finished {$class}">
    {apply messages with $messages->find('after_finish') as $messages /}
</form>

<div fx:template='messages' class='fx_form_messages' fx:with-each='$messages'>
    <div fx:item class="fx_form_message">{$message /}</div>
</div>

<div fx:template="row" class="{apply row_class}">
    {apply label /}
    {apply comment /}
    {apply errors /}
    {apply input_block /}
</div>

{template id="row_class"}
    fx_form_row fx_form_row_type_{$type} fx_form_row_name_{$name} 
    {if $_.errors} fx_form_row_error{/if}
    {if $required} fx_form_row_required{/if}
{/template}

<div fx:template="row[$type === 'hidden']" fx:omit='true'>
    {apply input /}
</div>

<div fx:template="row[$type === 'header']" class="{apply row_class}">
    <h2>{$%label}</h2>
</div>

<div fx:template="errors" fx:each="$_.errors as $error" class="fx_form_error">
    {$error}
</div>

<label fx:template="label" class="fx_label" for="{$id}" fx:if="!in_array($type, array('hidden', 'submit'))">
    <span class="fx_label_title">{$%label}</span>
    <span fx:if="$required" class="required">*</span>
</label>
    
<div fx:template="comment" class="fx_field_comment" fx:if="$%comment">
    <span class="fx_field_comment_text">{$%comment}</span>
</div>

<div fx:template="input_block" class="fx_input_block"> 
    {if $render.input}
        {apply $render.input}
    {else}
        {apply input /}
    {/if}
</div>

{template id="input_atts"}
    {set $is_textlike = in_array($type, array('text', 'number', 'password'))}
    class="fx_input fx_input_type_{$type}"
    id="{$id}"
    name="{$name}"
    {if $is_disabled}disabled="disabled"{/if}
    {if $is_textlike || $type == 'hidden'}
        value="{$value | htmlspecialchars}"
    {/if}
    {if $autocomplete === false}
        autocomplete="off"
    {/if}
    {if ($%placeholder || $_is_admin) && ($is_textlike || $type == 'textarea')}
        placeholder="{$%placeholder | htmlspecialchars}" 
    {/if}
{/template}

<input 
    fx:template="input[in_array($type, array('text', 'password', 'hidden'))]"
    type="{$type}"
    {apply input_atts /} />

<input 
    fx:template="input[$type == 'checkbox']"
    type="checkbox"
    {apply input_atts /}
    {if $value}checked="checked"{/if} />

<textarea
    fx:template="input[$type == 'textarea']"
    {apply input_atts /}>{$value | htmlentities}</textarea>

<button
    fx:template="input[$type == 'submit']"
    type="submit"
    class="fx_input fx_input_type_submit">
    <span>{$%label}Submit{/$}</span>
</button>

<select 
    fx:add="false"
    fx:template="input[$type == 'select']"
    {if $is_multiple}multiple="multiple"{/if}
    {apply input_atts /}>
    <option 
        fx:each="$values as $key => $name" 
        value="{$key}" 
        {if $value == $key}selected="selected"{/if}>{$name}</option>
</select>

<div fx:template="row[$type == 'select' && count($values) == 1 && $hidden_on_one_value]">
    <input type="hidden" {apply input_atts} value="{$values.key()}" />
</div>

<div class="fx_captcha_input" fx:template="input[$type == 'captcha']">
    <input {apply input_atts /} autocomplete="off" />
    <div class="fx_captcha_image_block">
        <img src="{$captcha_url}" class="fx_captcha_image" />
        <a class="fx_refresh_captcha">{%refresh_captcha}Show another image{/%}</a>
    </div>
</div>
    
<div class="fx_captcha_row_valid" fx:template="row[$type == 'captcha' && $was_valid]">
    <input type="hidden" {apply input_atts /} />
</div>

<div fx:template="input[$type == 'radio']">
    {set $field_name = $name}
    <label fx:each="$values as $key => $option" title="{$option.comment | strip_tags}" class="fx_form_option_label">
        <input type="radio" name="{$field_name}" value="{$key}" {if $value == $key}checked="checked"{/if} />
        <span>{$option.name}</span>
    </label>
</div>
    
<div fx:template="input[$type == 'checkbox_set']">
    {set $field_name = $name}
    <label fx:each="$values as $key => $option" title="{$option.comment | strip_tags}" class="fx_form_option_label">
        <input type="checkbox" name="{$field_name}[{$key}]" {if $value && $value.$key}checked="checked"{/if} />
        <span>{$option.name}</span>
    </label>
</div>
    
<div fx:template="input[$type == 'file' || $type == 'image']">
    {set $field_name = $name}
    <div fx:if="count($inputs) > 1" class="fx_file_switcher">
        <label fx:each="$inputs">
            <input type="radio" name="{$name}" {if $checked}checked="checked"{/if} value="{$type}" />
            <span>{$label}</span>
        </label>
    </div>
    <div fx:with-each="$inputs">
        <span class="fx_file_input {if !$checked}fx_file_input_inactive{/if} fx_file_input_{$type}" fx:item>
            <input type="{if $type == 'file'}file{else}text{/if}" name="{$field_name}[{$type}]" />
        </span>
    </div>
</div>
        

<div
    fx:template="input[$type == 'livesearch']"
    {set $f_postfix = $name_postfix ? '[' . $name_postfix . ']' : ''}
    {set $input_name = $name}
    class="livesearch" 
    data-params='{$params | json_encode }'
    data-prototype_name="{$name}[prototype]{$f_postfix}"
    data-is_multiple="{if $is_multiple}Y{else}N{/if}">
        {if $is_multiple && $value && !$ajax_preload}
            <input  
                fx:each="$value as $vi => $vv" 
                class="preset_value" type="hidden" 
                name="{$input_name}[{$vv.value_id}]{$f_postfix}"
                value="{$vv.id}"
                data-name="{$vv.name}" />
        {elseif !$is_multiple}
            <input 
                class="preset_value" 
                type="hidden" name="{$input_name}"
                {if $value}
                    value="{$value.id}" 
                    data-name="{$value.name}"
                {/if}
                />
        {/if}
    <ul class="livesearch_items">
        <li class="livesearch_input">
            <input type="text" class="livesearch_input" {*name="livesearch_input"*} autocomplete="off" style="width:3px;" />
        </li>
    </ul>
    <div class="livesearch_results">
    </div>
</div>