(function($) {
    if ($('.pagination').length) {
        $('.ss-orderable').on('click','.pagination a', function (e) {
            e.preventDefault();
            var url = $(this).attr('href');
            $.ajax(url)
                .done(function (response) {
                    $('.ss-orderable').html(response);
                    window.history.pushState(null,'',url)
                })
                .fail (function (xhr) {
                    alert('Error: ' + xhr.responseText);
                });
        });
    }
    
    $.entwine("ss", function($) {

        /**
         * GridFieldOrderableRows
         */
        var isDragged = false;

        $(".ss-orderable .ui-sortable").entwine({
            onadd: function() {
                var update = function() {
                    var idsInOrder = $(this).sortable("toArray");
                    if (!isDragged) {
                        $('.ss-orderable').load(
                                document.location.href,
                                {order: idsInOrder, doOrder: 1}
                        );
                    } else
                        isDragged = false;
                };

                this.sortable({
                    handle: ".handle",
                    opacity: .7,
                    update: update
                });
            },
            onremove: function() {
                this.sortable("destroy");
            }
        });

        $(".ss-previouspage, .ss-nextpage").entwine({
            onadd: function() {

                if (this.is(":disabled")) {
                    return false;
                }

                var drop = function(e, ui) {
                    var page;

                    if ($(this).hasClass("ss-previouspage")) {
                        page = "prev";
                    } else {
                        page = "next";
                    }

                    isDragged = true;
                    $('.ss-orderable').load(
                            document.location.href,
                            [
                                {name: "move[id]", value: ui.draggable.attr("id")},
                                {name: "move[page]", value: page},
                                {name: "doOrder", value: 1}
                            ]);

                };

                this.droppable({
                    accept: ".ss-item",
                    activeClass: "ui-droppable-active",
                    disabled: this.prop("disabled"),
                    drop: drop,
                    tolerance: "pointer"
                });
            },
            onremove: function() {
                if (this.hasClass("ui-droppable"))
                    this.droppable("destroy");
            }
        });
    });
})(jQuery);
