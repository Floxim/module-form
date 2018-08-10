{template id="form_block" of="form, floxim.user.user:auth_form, *:form" name="Форма"}
    {apply form with $form /}
{/template}

<form 
    fx:template="form" 
    fx:with="$form"
    fx:b="form 
        {if $form.is_sent}sent{/if}
        {if $form.ajax}ajax{/if}
    " 
    {if $form.captcha_expression}
        data-ce="{$form.captcha_expression /}"
    {/if}
    action="" 
    {if $action}data-ajax-action="{$action}"{/if}
    fx:styled-inline
    method="post"
    {if $form.redraw}data-fx_redraw="{$form.redraw | htmlspecialchars /}"{/if}
    >
    
    {first}
        {set $form = $ /}
        {if $form}
            {= $form.prepare() /}
        {/if}
    {/first}
    
    {if $form.add_fake_inputs}
        {apply fake_inputs /}
    {/if}
    
    {$form.getHidden() || :input /}
    
    {if !$form.is_finished}
        
        {apply messages with $messages = $form.messages_before /}
        
        {apply errors /}
        
        <div fx:e="body">
            <div fx:e="inputs">
                {apply inputs /}
            </div>

            <div fx:e="buttons">
                {$form.getButtons() || :input /}
            </div>
        </div>
        <div fx:e="footer-text">
            {%form_footer_text label="Текст под формой" type="html" linebreaks="false" /}
        </div>

    {else}
        {apply messages with $messages = $form.messages_after /}
    {/if}
    
    {js}
        FX_JQUERY_PATH
        Floxim.js from floxim.ui.js
        form.js
    {/js}
</form>

{template id="inputs"}
    <div fx:each="$form.getInputs() as $field" 
        fx:e="row {if $field.has_errors} has-errors{/if}" 
        fx:b="field type_{$field.field_type /}">
       {apply floxim.ui.box:box with $item = $field, $box_id = 'fieldbox', $field_source = 'item' /}
   </div>
{/template}

<label fx:template="label" for="{$item.field_id}" fx:nows>
    {@param name="show_asterisk" label="Показывать звездочку?" type="checkbox" /}
    {set $label_text}
        {$item.label /}
        {if $item.required && $show_asterisk}
            &nbsp;<span class="floxim--form--form--form__asterisk">*</span>
        {/if}
    {/set}
    {apply floxim.main.text:text with $text = $label_text /}
</label>

<div fx:b="control" fx:template="control" fx:styled="Стиль поля">
    {apply input with $field = $item, $item /}
</div>
    
<div fx:template="errors" fx:e="errors" fx:b="floxim.main.text:text" fx:styled="label:Стиль ошибки" fx:if="count($errors)">
    <p fx:e="error" fx:each="$errors as $error">
        {if gettype($error) === 'string'}
            {$error /}
        {else}
            {$error.text /}
        {/if}
    </p>
</div>
    
<div fx:template="messages" fx:e='messages' fx:if='count($messages)' class='fx_no_add'>
    {set $box_label = 'Сообщения' /}
    {each $messages as $message}
        {apply floxim.ui.box:box el message with $item = $message /}
    {/each}
</div>
        
<input 
    fx:template="input" 
    name="{$name editable='false'}"
    id="{$field_id}"
    type="{$field_type}"
    fx:e="input type_{$field_type} {if $field.icon}has-icon{/if}"
    {*autocomplete="off"*}
    {if $field_type === 'text' || $field_type === 'password'}
        placeholder="{$placeholder /}"
    {/if}
    value="{$display_value | htmlspecialchars}"
    />

<input 
    fx:template="input[$field_type === 'checkbox']" 
    name="{$name editable='false'}"
    id="{$field_id}"
    type="checkbox"
    fx:e="input type_checkbox"
    {if $display_value}checked="checked"{/if}
    />

{template id="input" test="$field_type == 'button'"}
    {apply button /}
{/template}

<button 
    fx:template="button" 
    fx:e="button"
    fx:b="button"
    fx:styled="label:Стиль кнопки"
    {if $button_data}data-button_data='{$button_data | json_encode /}'{/if}
    {if $field_id}id="{$field_id}"{/if}
    name="{$field.name /}"
    >
    <span fx:e="label">{$label /}</span>
    <span fx:if="$icon" fx:e="icon" class="{= fx::icon( $icon )}"></span>
</button>

<a 
    href="{$button_link}"
    {if $button_link_target}target="{$button_link_target}"{/if}
    fx:template="button[$button_link]"
    fx:e="button"
    fx:b="button"
    fx:styled="label:Стиль кнопки">
    <span fx:e="label">{$label /}</span>
    <span fx:if="$icon" fx:e="icon" class="{= fx::icon( $icon )}"></span>
</a>

<textarea
    fx:template="input[$field_type == 'textarea']"
    id="{$field_id}"
    rows="{$rows}"
    fx:e="input type_textarea"
    placeholder="{$placeholder /}"
    name="{$field.name}">{$field.value | htmlentities /}</textarea>
    
    
<div fx:template="fake_inputs" style="position:fixed; top:-10000px;">
    <input type="text" name="fakename" tabindex="-1" style="opacity:0;"/>
    <input type="password" name="fakepassword" tabindex="-1" style="opacity:0;" />
</div>
    
    
<div fx:template="input[$field.field_type == 'group']" fx:b="group">
    <div fx:each="$field.fields as $sub_field">
        {set $field = $sub_field /}
        {apply input with $field /}
    </div>
</div>

<div
    fx:template="input[$field_type === 'select' && $field_view === 'radio']"
    fx:e="input type_radio"
    fx:b="radio layout_{$layout /}">
    {default $field_value = $value ? $value : $values[0].value /}
    {first}
        {set $layout = $field.getTotalLength() > 50 ? 'vertical' : 'horizontal' /}
        {css}radio.less{/css}
    {/first}
    {set $field_name = $name /}
    <label fx:each="$values as $val" fx:e="item">
        <input
            type="radio"
            name="{$field_name /}"
            value="{$val.value /}"
            {if $val.value == $field_value}checked="checked"{/if}
            fx:e="input"
        />
        <span fx:e="label">{$val.name /}</span>
    </label>
</div>
    
<select 
    fx:template="input[$field_type == 'select']"
    id="{$field_id /}"
    fx:e="input type_select"
    name="{$name /}">
    {default $field_value = $value /}
    <option 
        fx:each="$values as $val"
        value="{$val.value /}"
        {if $field_value == $val.value}selected="selected"{/if}>
        {$val.name /}
    </option>
</select>