(function($) {

$('html').on('click', '.fx_refresh_captcha', function() {
    var $pic = $(this).closest('.fx_form_row').find('.fx_captcha_image');
    var src = $pic.attr('src');
    var new_src = src.replace(/rand=\d+/, 'rand='+Math.round(Math.random()*10000));
    $pic.attr('src', new_src);
    $pic.closest('.fx_captcha_input').find('.fx_input_type_captcha').val('').focus();
});

$('html').on('change', '.fx_file_switcher', function() {
    var c_value = $('input:checked', this).attr('value');
    var $block = $(this).closest('.fx_input_block');
    $('.fx_file_input', $block).hide();
    $('.fx_file_input_'+c_value, $block).show();
});

$('html').on('click', '.fx_form :input[type="submit"]', function() {
    var $b = $(this),
        $form = $b.closest('form');
    $form.data('fx_pressed_button_name', $b.attr('name'));
});

$('html').on('submit', 'form.fx_form_ajax', function(e) {
    var $form = $(this);
    var event_before = $.Event('fx_before_ajax_form_sent');
    $form.trigger(event_before);
    if (event_before.isDefaultPrevented()) {
        return false;
    }
    if ($('input[name="_ajax_base_url"]', $form).length === 0) {
        $form.append('<input type="hidden" name="_ajax_base_url" value="'+document.location.href+'" />');
    } 
    var button_name = $form.data('fx_pressed_button_name');
    if (button_name) {
        var $button_placeholder = $('<input type="hidden" name="'+button_name+'" value="1" />');
        $form.append($button_placeholder);
    }
    var form_data = $form.serialize();
    if (button_name) {
        $button_placeholder.remove();
    }
    $.ajax({
        type:'post',
        url:$form.attr('action'),
        dataType:'html',
        data:form_data,
        success: function(data) {
            var $data = $(data);
            var $ib = $form.closest('.fx_infoblock');
            var $container = $ib.parent();
            $ib.before($data);
            var event_reload = $.Event('fx_form_reloaded', {reloaded:$data});
            $form.trigger(event_reload);
            $ib.remove();
            $('form.fx_form_sent .fx_form_row_error :input', $container).first().focus();
            $data.trigger('fx_form_loaded').trigger('fx_infoblock_loaded');
        }
    });
    return false;
});

$(function() {
    $('.fx_input_block .livesearch').each(function() {
        var $node = $(this);
        var ls = new fx_livesearch($node);
        $node.data('livesearch', ls);
        if (ls.ajax_preload) {
            ls.loadValues(ls.plain_values);
        }
    });
});

var handle_date_field = function($block) {
    $block.ctx('fx-date-field');
    
    var $inp  = $block.find('.fx_input');
    
    function export_parts() {
        var res = '',
        filled = true;
        $.each(
            'y,m,d,h,i'.split(','), 
            function(index, item) {
                var c_val = $block.elem('part').byMod('type', item).val();
                if (!c_val) {
                    if (item === 'h' || item === 'i') {
                        c_val = '00';
                    } else {
                        filled = false;
                    }
                }
                res += c_val;
                res += (index < 2 ? '-' : index === 2 ? ' ' : ':');
            }
        );
        res += '00';
        if (filled) {
            var date = new Date(res);
            if (date && !isNaN(date.getTime())) {
                $inp.val( format_date ( date ) );
            }
        }
    };
    
    function format_date(d) {
        var res = $.datepicker.formatDate("yy-mm-dd", d );
        res += ' ';
        var h = d.getHours();
        res += (h < 10 ? '0' : '')+h + ':';
        var m = d.getMinutes();
        res += (m < 10 ? '0' : '')+m+':00';
        return res;
    }
    
    $block.elem('part').on('keydown',  function(e) {
        var $part = $(this),
            part_val = $part.val(),
            max = $part.data('max'),
            min = $part.data('min') || 0,
            len = $part.data('len'),
            strikes = ( $part.data('strikes') || 0) + 1;
        
        $part.data('strikes', strikes);
    
        if (e.which === 40 || e.which === 38) { // down or up
            part_val = part_val*1;
            part_val += (e.which === 40 ? -1 : 1);
            if (part_val < min) {
                part_val = max;
            } else if (part_val > max) {
                part_val = min;
            }
            
            if (len === 2 && part_val < 10) {
                part_val = '0'+part_val;
            }
            
            $part.val(part_val);
            return false;
        }
    })
    .on('focus mouseup click',  function(e) {
        this.setSelectionRange(0, this.value.length);
        $(this).data('strikes', 0);
        return false;
    })
    .on('keyup', function(e) {
        var $part = $(this),
            part_val = $part.val(),
            min = $part.data('min'),
            max = $part.data('max'),
            len = $part.data('len');
        
        if (part_val.length > len) {
            part_val = part_val.slice(0, len);
        }
        
        if (part_val.match(/[^0-9]/)) {
            part_val = part_val.replace(/[^0-9]+/g, '');
        }
        
        var int_val = part_val*1;
        
        if (int_val > max) {
            part_val = max;
        }
        if (part_val + '' !== $part.val()) {
            $part.val(part_val);
        }
        
        export_parts();
        
        if (this.selectionStart !== undefined && this.selectionStart === this.selectionEnd) {
            if (this.selectionStart === 0 && e.which === 37) {
                var $prev = $part.prevAll('.fx-date-field__part').first();
                if ($prev.length) {
                    $prev.focus().focus();
                }
            } else if (this.selectionEnd === part_val.length && e.which === 39) {
                var $next = $part.nextAll('.fx-date-field__part').first();
                if ($next.length) {
                    $next.focus().focus();
                }
            }
        }
        
        if (e.which < 48 || e.which > 57 || !$part.data('strikes')) { 
            return;
        }
        
        if (part_val.length === $part.data('len')) {
            var int_val = part_val*1;
            if (int_val >= min && int_val <= max) {
                var $next = $part.nextAll('.fx-date-field__part').first();
                if ($next.length) {
                    $next[0].setSelectionRange(0, $next.val().length);
                    $next.focus().focus();
                }
            }
        }
    });

var show_format = 'yy-mm-dd';

$inp.datepicker({
    changeMonth: true,
    changeYear: true,
    firstDay:1,
    dateFormat: show_format,
    onSelect:function(dateText, datepicker) {
        var d = new Date(dateText);
        $('.fx_date_part_d', html).val( $.datepicker.formatDate("dd", d) );
        $('.fx_date_part_m', html).val( $.datepicker.formatDate("mm", d) );
        $('.fx_date_part_y', html).val( $.datepicker.formatDate("yy", d) );
        export_parts();
    }
});

//$inp.datepicker('widget').addClass('fx_overlay');

$block.elem('datepicker_icon').click(function() {
    $inp.datepicker('show');
});

};

$(function() {
    $('.fx_form .fx_input_type_wysiwyg').redactor({
            
    });
    
    $('.fx_form .fx-date-field').each(function() {
        handle_date_field($(this));
    });
});


})(window.jQuery);