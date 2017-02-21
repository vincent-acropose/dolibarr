$(function() {

    function toFloat(value)
    {
        return parseFloat(value.replace(modvatcalculator_decsep,'.').replace(modvatcalculator_thsep,''));
    }

    function toString(value)
    {
        return parseFloat(Math.round(value * 100) / 100).toFixed(2).replace('.',modvatcalculator_decsep);
    }

    function updateTaxInc()
    {
        var value = input_ht.val();
        if (value.length) {
            input_ttc.val(toString(toFloat(value) * (1 + select_tax.val()/100)));
        }
    }

    function updateTaxExc()
    {
        var value = input_ttc.val();
        if (value.length) {
            input_ht.val(toString(toFloat(value) / (1 + select_tax.val()/100)));
        }
    }

    function updateDisplay(show)
    {
        var display = show ? 'block' : 'none';

        input_ttc.parent().css('display', display);
        input_ht.parent().css('display', display);
    }

    var input_ht = $('input[name="price_ht"]'),
        select_mode = $('input[name="prod_entry_mode"]'),
        select_free = $('#select_type');
        input_predef = $('#idprod, #idprodfournprice');

    // Remove unit ttc column if exists
    if ($('td.linecoluttc').length) {
        $('#cancellinebutton, #addline').closest('tr').find('td:nth-child(4)').text('');
    }

    if (input_ht.length) {
        var input_ttc   = $('<input/>').addClass('flat').attr({ type: 'text', size: 8 }),
            select_tax  = $('#tva_tx'),
            label_ttc   = $('<label/>').text(modvatcalculatorlang_ttclabel),
            span_ttc    = $('<div/>').css('width','100%').append(label_ttc,input_ttc),
            label_ht    = $('<label/>').text(modvatcalculatorlang_htlabel),
            tablecell   = input_ht.parent();

        // Force input size
        input_ht.attr('size', 8);

        // Rename column
        $('#title_up_ht').text(modvatcalculatorlang_pulabel);
        $('#tablelines .liste_titre>td:nth-child(3)').removeAttr('width');

        // Append fields
        tablecell.append(
            $('<div/>').append(label_ht,input_ht.detach()),
            $('<div/>').append(label_ttc,input_ttc)
        );

        // First update
        updateTaxInc();

        // Events
        input_ht.on('keyup', updateTaxInc);
        input_ttc.on('keyup', updateTaxExc);
        select_tax.on('change', updateTaxInc);
    }

    // Listen events to show/hide inputs
    input_predef.on('change', function() {
        updateDisplay(false);
    });
    select_mode.on('change', function(event) {
        if($(event.target).val() == 'free') {
            updateDisplay(true);
        }
        else {
            updateDisplay(false);
        }
    });
    select_free.on('change', function() {
        updateDisplay(true);
    });

});
