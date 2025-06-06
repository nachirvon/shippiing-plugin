jQuery(document).ready(function ($) {
    //Initiate Color Picker
    $(".amdsp-color-picker-field").wpColorPicker();

    // Switches option sections
    var activetab = "";
    if (typeof (localStorage) != "undefined") {
        activetab = localStorage.getItem("activetab");
    }

    // if url has section id as hash then set it as active or override the current local storage value
    if (window.location.hash) {
        activetab = window.location.hash;
        if (typeof (localStorage) != "undefined") {
            localStorage.setItem("activetab", activetab);
        }
    }

    if (activetab != "" && $(activetab).length) {
        $(activetab).fadeIn();
    } else {
        $(".amdsp-group:first").fadeIn();
    }
    $(".amdsp-group .collapsed").each(function () {
        $(this).find("input:checked").parent().parent().parent().nextAll().each(
            function () {
                if ($(this).hasClass("last")) {
                    $(this).removeClass("hidden");
                    return false;
                }
                $(this).filter(".hidden").removeClass("hidden");
            });
    });

    if (activetab != "" && $(activetab + "-tab").length) {
        $(activetab + "-tab").addClass("nav-tab-active");
    } else {
        $(".nav-tab-wrapper a:first").addClass("nav-tab-active");
    }
    $(".nav-tab-wrapper a").click(function (evt) {
        $(".nav-tab-wrapper a").removeClass("nav-tab-active");
        $(this).addClass("nav-tab-active").blur();
        var clicked_group = $(this).attr("href");
        if (typeof (localStorage) != "undefined") {
            localStorage.setItem("activetab", $(this).attr("href"));
        }
        $(".amdsp-group").hide();
        $(clicked_group).fadeIn();
        evt.preventDefault();
    });

    $(".amdsp-browse").on("click", function (event) {
        event.preventDefault();

        var self = $(this);

        // Create the media frame.
        var file_frame = wp.media.frames.file_frame = wp.media({
            title: self.data("uploader_title"),
            button: {
                text: self.data("uploader_button_text"),
            },
            multiple: false,
        });

        file_frame.on("select", function () {
            attachment = file_frame.state().get("selection").first().toJSON();
            self.prev(".amdsp-url").val(attachment.url).change();
        });

        // Finally, open the modal
        file_frame.open();
    });
});