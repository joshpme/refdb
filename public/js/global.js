// Ajax modal window
$(document).on("click", ".btn-modal", function() {


    var $ajaxModal = $("#ajaxModal");

    if($(this).hasClass("btn-modal-lg")) {
        $ajaxModal.find(".modal-dialog").addClass("modal-lg");
    } else {
        $ajaxModal.find(".modal-dialog").removeClass("modal-lg");
    }

    if ($(this)[0].tagName === "A") {
        console.log($(this).attr("href"));
        $ajaxModal.data("href", $(this).attr("href"));
    } else {
        $ajaxModal.data("href", $(this).data("href"));
    }

    $ajaxModal.data("reload", $(this).data("reload"));

    if (($ajaxModal.data('bs.modal') || {})._isShown) {
        ajaxModalShow($ajaxModal);
    } else {
        $ajaxModal.modal('show');
    }

    return false;
});

var ajaxModalShow = function($container) {
    $('.modal-content').html('<div class="modal-body text-center"><i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i></div>');
    $.get({
        url: $container.data("href"),
        success: function(response) {
            $container.find(".modal-content").html(response);
            $container.find(".modal-content form").on("submit", ajaxForm);
        }, error: function(jqXHR, textStatus, errorThrown) {
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
            $container.find(".modal-content").html('<div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button><h4 class="modal-title text-danger"><i class="material-icons">error</i> Something went wrong</h4></div>' +
                '<div class="modal-body"><p>An error occurred trying to perform that task.</p><p>Try reloading the page and try again. If the problem persists, contact the service desk on x9200</p></div>' +
                '<div class="modal-footer"><button class="btn btn-default" data-dismiss="modal">Close</button></div>' +
                '</div>')
        }
    });
};

var ajaxModalHide = function($container) {
    if($container.data("reload") !== undefined && $container.data("reload") !== "") {
        if($container.data("reload") === "reload") {
            location.reload(true);
        }
    }
    $(this).data("reload", "");
};

var ajaxForm = function () {
    var container = $(this);
    $(this).find('.btn').attr('disabled', true);
    $.post({
        url: $(this).attr("action"),
        data: $(this).serialize(),
        success: function (response, status, xhr) {

            var ct = xhr.getResponseHeader("content-type") || "";
            if (ct.indexOf('html') > -1) {
                var wrapper = container.closest(".modal-content");
                wrapper.html(response);
                wrapper.find("form").on("submit", ajaxForm);
                wrapper.find('.btn').attr('disabled', false);
            }
            if (ct.indexOf('json') > -1) {
                if (response.success) {
                    if (typeof response.redirect !== undefined) {
                        location.href = response.redirect;
                    }
                }
            }

        }
    }).fail(function () {
        container.closest(".modal-content").html('<div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button><h4 class="modal-title text-danger"><i class="material-icons">error</i> Something went wrong</h4></div>' +
            '<div class="modal-body"><p>An error occurred trying to perform that task.</p><p>Try reloading the page and try again. If the problem persists, contact the service desk on x9200</p></div>' +
            '<div class="modal-footer"><button class="btn btn-default" data-dismiss="modal">Close</button></div>' +
            '</div>');
    });
    return false;
};

$('.ajaxModal')
    .on('show.bs.modal', function() {ajaxModalShow($(this))})
    .on('hide.bs.modal', function() {ajaxModalHide($(this))});


if (!String.prototype.startsWith) {
    String.prototype.startsWith = function(searchString, position) {
        position = position || 0;
        return this.indexOf(searchString, position) === position;
    };
}

if (!String.prototype.endsWith) {
    String.prototype.endsWith = function(search, this_len) {
        if (this_len === undefined || this_len > this.length) {
            this_len = this.length;
        }
        return this.substring(this_len - search.length, this_len) === search;
    };
}

$(document).ready(function(){
    $("#filter").submit(function(){
        var val = $("#filterValue").val();
        if (!val.endsWith('*'))
            val = val + "*" ;

        if (!val.startsWith('*'))
            val = "*" + val;

        $("#filterValue").val(val);
    });

    $('textarea').each(function () {
        this.setAttribute('style', 'height:' + (this.scrollHeight + 10) + 'px;');
    }).on('input', function () {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
});

$('input[type=file]').on('change',function(e){
    var fileName = e.target.files[0].name;
    $(this).closest(".custom-file").find('.custom-file-label').html(fileName);
});

$('.js-datepicker').datepicker({
    format: 'dd/mm/yyyy'
});

function toDate(dateString) {
    var dateParts = dateString.split("/");
    return new Date(+dateParts[2], dateParts[1] - 1, +dateParts[0]); 
}

function monthYear(date) {
    month = date.toLocaleString('default', { month: 'long' });
    year = date.getFullYear();

    if (month.length > 3) {
        month = month.substring(0, 3) + ".";
    }
    return {
        month: month, 
        year: year 
    };
}

function produceConfDate() {
    start = monthYear(toDate($(".conference-date-start").val()));
    end = monthYear(toDate($(".conference-date-end").val()));

    if (!isNaN(end.year) && !isNaN(start.year)) {
        newDate = "";
        if (start.month != end.month) {
            if (start.year != end.year) {
                newDate = start.month + " " + start.year + "-" + end.month + " " + end.year
            } else {
                newDate = start.month + "-" + end.month + " " + end.year
            }
        } else {
            newDate = end.month + " " + end.year
        }
        $(".conference-date-formatted").val(newDate);
    }
}
$(".conference-date").blur(function(){
    produceConfDate();
});


function format_date(content) {
    let date = new Date(Date.parse(content));
    return date.getUTCDate() + "/" +
        (date.getUTCMonth()+1) + "/" +
        date.getUTCFullYear();
}

$("#pre-fill-action").click(function(){
    let content = $("#pre-fill-content").val();

    $.post(Routing.generate("conference_parser"), {"content": content},
        function(response) {

            if (typeof response.conference_titleshrt !== 'undefined') {
                $("#appbundle_conference_name").val(response.conference_titleshrt);
            }

            if (typeof response.conference_series !== 'undefined') {
                $("#appbundle_conference_series").val(response.conference_series);
            }
            if (typeof response.conference_series !== 'undefined') {
                $("#appbundle_conference_seriesNumber").val(response.conference_number);
            }
            if (typeof response.conference_site !== 'undefined') {
                $("#appbundle_conference_location").val(response.conference_site);
            }

            if (typeof response.conference_date !== 'undefined') {
                let conference_dates = response.conference_date.split("/");
                let start = new Date(Date.parse(conference_dates[0]));
                let end = new Date(Date.parse(conference_dates[1]));
                $("#appbundle_conference_conferenceStart").val(format_date(start));
                $("#appbundle_conference_conferenceEnd").val(format_date(end));
                produceConfDate();
            }

            if (typeof response.conference_isbn !== 'undefined') {
                $("#appbundle_conference_isbn").val(response.conference_isbn);
            }
            if (typeof response.series_issn !== 'undefined') {
                $("#appbundle_conference_issn").val(response.series_issn);

            }
            if (typeof response.conference_pub_date !== 'undefined') {
                let pub_date = new Date(Date.parse(response.conference_pub_date));
                $("#appbundle_conference_isPublished").prop("checked", true);
                $("#appbundle_conference_pubYear").val(pub_date.getUTCFullYear());
                $("#appbundle_conference_pubMonth").val(pub_date.getUTCMonth()+1);
            }

            if (typeof response.conference_url !== 'undefined') {
                $("#appbundle_conference_baseUrl").val(response.conference_url);
            }

            if (typeof response.conference_name !== 'undefined') {
                $("#appbundle_conference_doiCode").val(response.conference_name);
                $("#appbundle_conference_useDoi").val(1);
            }
        });
});