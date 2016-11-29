(function($) {
    
var ns = 'floxim--form--form';

var QueryStringToHash;

(function() {
    var re = /([^&=]+)=?([^&]*)/g;
    var decodeRE = /\+/g;  // Regex for replacing addition symbol with a space
    var decode = function (str) {return decodeURIComponent( str.replace(decodeRE, " ") );};
    QueryStringToHash = function(query) {
        var params = {}, e;
        while ( e = re.exec(query) ) { 
            var k = decode( e[1] ), v = decode( e[2] );
            if (k.substring(k.length - 2) === '[]') {
                k = k.substring(0, k.length - 2);
                (params[k] || (params[k] = [])).push(v);
            }
            else params[k] = v;
        }
        return params;
    };
})();
    


$('html').on('click', '.'+ns+'--form :input[type="submit"]', function() {
    var $b = $(this),
        $form = $b.closest('form');
    $form.data('pressed_button_name', $b.attr('name'));
});

$('html').on('submit', '.'+ns+'--form_ajax', function(e) {
    var $form = $(this);
    var event_before = $.Event('fx_before_ajax_form_sent');
    $form.trigger(event_before);
    if (event_before.isDefaultPrevented()) {
        return false;
    }
    
    var button_name = $form.data('pressed_button_name');
    if (button_name) {
        var $button_placeholder = $('<input type="hidden" name="'+button_name+'" value="1" />');
        $form.append($button_placeholder);
    }
    var form_data = QueryStringToHash($form.serialize());
    if (button_name) {
        $button_placeholder.remove();
    }
    
    var $ib = $form.closest('.fx_infoblock');
    
    Floxim.ajax({
        url: $form.attr('action'),
        data: form_data,
        $block: $ib
    }).then(function(data) {
        var $data = $(data);
        var $container = $ib.parent();
        $ib.before($data);
        var event_reload = $.Event('fx_form_reloaded', {reloaded:$data});
        $form.trigger(event_reload);
        $ib.remove();
        $('.'+ns+'--form_sent .'+ns+'--form__row_has-errors :input', $container).first().focus();
        $data.trigger('fx_infoblock_loaded');
    });
    return false;
});

var handle_date_field = function($block) {
    $block.ctx('fx-date-field');
    
    var $inp  = $block.find('.fx_input');
    
    function export_parts() {
        var res = '',
            months = {
                '01':'Jan', '02':'Feb', '03':'Mar', '04':'Apr', '05':'May', '06':'Jun',
                '07':'Jul', '08':'Aug', '09':'Sep', '10':'Oct', '11':'Nov', '12':'Dec'
            },
            filled = true;
        $.each(
            'd,m,y,h,i'.split(','), 
            function(index, item) {
                var c_val = $block.elem('part').byMod('type', item).val();
                if (!c_val) {
                    if (item === 'h' || item === 'i') {
                        c_val = '00';
                    } else {
                        filled = false;
                    }
                }
                if (item === 'm') {
                    c_val = months[c_val];
                }
                res += c_val;
                res += (index < 2 ? ' ' : index === 2 ? ' ' : ':');
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

function init_controls($node) {
    $('.fx_form .fx_input_type_wysiwyg', $node).each(function() {
        $(this).redactor({});
    });

    $('.fx_form .fx-date-field', $node).each(function() {
        handle_date_field($(this));
    });
    
    $('.livesearch', $node).each(function() {
        var $node = $(this);
        var ls = new fx_livesearch($node);
        $node.data('livesearch', ls);
        if (ls.ajax_preload) {
            ls.loadValues(ls.plain_values);
        }
    });
}

$(function() {
    init_controls($('body'));
});

$('html').on('fx_infoblock_loaded', function(e) {
    var $form = $('.fx_form', e.target);
    $form.trigger('fx_form_loaded');
});

$('html').on('fx_form_loaded', function(e) {
    init_controls($(e.target.parentNode));
});


})(window.jQuery);