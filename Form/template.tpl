<div fx:template="form_block" fx:of="form">
    {apply form with $form /}
</div>

<form 
    fx:template="form" 
    fx:with="$form"
    fx:b="form 
        {if $form.is_sent}sent{/if}
        {if $form.ajax}ajax{/if}
    " 
    fx:styled='label:Стиль формы'
    action="{$action}" 
    method="post">
    
    {apply fake_inputs /}
    
    {first}
        {set $form = $ /}
        {if $form}
            {= $form.prepare() /}
        {/if}
    {/first}
    
    {$form.getHidden() || :input /}
    
    {apply errors /}
    
    
    <div fx:e="inputs">
        <div fx:each="$form.getInputs() as $field" 
             fx:e="row {if $field.has_errors} has-errors{/if}" 
             fx:b="field type_{$field.field_type /}"
             fx:styled="label:Стиль полей">

            <label fx:e="label" for="{$field.field_id}">{$field.label /}</label>
            {apply errors with $errors = $field.errors /}
            <div fx:e="control">
                {apply input /}
            </div>
        </div>
    </div>
    
    <div fx:e="buttons">
        {$form.getButtons() || :input /}
    </div>
    
    {js}
        FX_JQUERY_PATH as jquery
        form.js
    {/js}
</form>
    
    
<div fx:template="errors" fx:e="errors" fx:if="count($errors)">
    <div fx:e="error" fx:each="$errors as $error">
        {$error /}
    </div>
</div>
        
<input 
    fx:template="input" 
    name="{$name editable='false'}"
    id="{$field_id}"
    type="{$field_type}"
    fx:e="input type_{$field_type}"
    autocomplete="off"
    {if $field_type === 'text' || $field_type === 'password'}
        placeholder="{$placeholder /}"
    {/if}
    value="{$value | htmlspecialchars}"
    />

<input 
    fx:template="input[$field_type === 'checkbox']" 
    name="{$name editable='false'}"
    id="{$field_id}"
    type="checkbox"
    fx:e="input type_checkbox"
    {if $value}checked="checked"{/if}
    />

<button 
    fx:template="input[$field_type == 'button']" 
    fx:e="button"
    fx:b="button"
    fx:styled="label:Стиль кнопки"
    id="{$field_id}">
    {$label /}
</button>

<textarea
    fx:template="input[$field_type == 'text' && $is_long]"
    id="{$field_id}"
    fx:e="input type_textarea"
    placeholder="{$placeholder /}"
    name="{$field.name}">{$field.value | htmlentities /}</textarea>
    
    
<div fx:template="fake_inputs" style="position:fixed; top:-10000px;">
    <input type="text" name="fakename" tabindex="-1" style="opacity:0;"/>
    <input type="password" name="fakepassword" tabindex="-1" style="opacity:0;" />
</div>