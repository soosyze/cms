$(function () {
    $('#form_filter_node').on('keyup change', debounce(function () {
        const $this = $(this);
        $.ajax({
            url: $this.attr('action'),
            type: $this.attr('method'),
            data: $this.serialize(),
            dataType: 'html',
            success: function (data) {
                $('tbody').replaceWith(data);
            }
        });
    }, 250));

    $('#form-node .tab-pane').each(function () {
        let idPane = $(this).attr("id");

        $(this).find('input, textarea, select').each(function () {

            if ($(this).hasClass('is-invalid')) {
                const error = `
                    <span class="fieldset-error" title="Error">
                        <i class='fa fa-exclamation-triangle' aria-hidden="true"></i>
                    <span>`;

                $(`ul a[href="#${idPane}"]`).css("color", "red");
                $(`ul a .fieldset-error`).remove();
                $(`ul a[href="#${idPane}"]`).append(error);

                return false;
            }

            $(`ul a[href="#${idPane}"]`).css("color", "inherit");
        });
    });

    const checkValidateFormNode = function () {
        $('#form-node .tab-pane').each(function () {
            let idPane = $(this).attr("id");

            $(this).find('input, textarea, select').each(function () {

                if (this.checkValidity() === false || $(this).hasClass('is-invalid')) {
                    const error = `
                    <span class="fieldset-error" title="Error">
                        <i class='fa fa-exclamation-triangle' aria-hidden="true"></i>
                    <span>`;

                    $(`ul a[href="#${idPane}"]`).css("color", "red");
                    $(`ul a .fieldset-error`).remove();
                    $(`ul a[href="#${idPane}"]`).append(error);

                    return false;
                }

                $(`ul a[href="#${idPane}"]`).css("color", "inherit");
            });
        });
    };

    $('#form-node #submit').on('click', checkValidateFormNode);
});