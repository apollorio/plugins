(function ($) {
    'use strict';

    $(document).ready(function () {


        var cbxuseronline_admin_awn_options = {
            labels: {
                tip          : cbxuseronline_admin.awn_options.tip,
                info         : cbxuseronline_admin.awn_options.info,
                success      : cbxuseronline_admin.awn_options.success,
                warning      : cbxuseronline_admin.awn_options.warning,
                alert        : cbxuseronline_admin.awn_options.alert,
                async        : cbxuseronline_admin.awn_options.async,
                confirm      : cbxuseronline_admin.awn_options.confirm,
                confirmOk    : cbxuseronline_admin.awn_options.confirmOk,
                confirmCancel: cbxuseronline_admin.awn_options.confirmCancel
            }
        };

        //send ajax request for refresh field
        $('#refreshtimenow_trig').on('click', function (e) {
            e.preventDefault();

            let $this = $(this);
            $this.addClass('disabled');
            $this.addClass('running');

            $.ajax({
                type    : 'post',
                dataType: 'json',
                url     : cbxuseronline_admin.ajaxurl,
                data    : {
                    action  : 'cbxuseronline_online_user_record_clean',
                    security: cbxuseronline_admin.nonce
                },
                success : function (data, textStatus, XMLHttpRequest) {

                    $this.removeClass('disabled');
                    $this.removeClass('running');


                    if (data.success) {
                        new AWN(cbxuseronline_admin_awn_options).success(data.message);
                        window.location.href = data.url;
                    } else {
                        new AWN(cbxuseronline_admin_awn_options).alert(data.message);
                    }
                },
                error   : function (jqXHR, textStatus, errorThrown) {
                    $this.data('busy', 0);
                    $this.removeClass('disabled');
                    $this.removeClass('running');
                }
            });// end of ajax


        });


        $('#cbxuseronline_table_data').tablesorter({

        });
    });

})(jQuery);