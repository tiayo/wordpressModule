$(document).ready(function() {
    $('#m-callback-start').click(function() {
        $('#m-callback-update').html('');
        $('#m-callback-done').html('');
        var $pb = $('.m-callback .progress-bar');
        $pb.attr('data-transitiongoal', $pb.attr('data-transitiongoal-backup'));
        $pb.progressbar({
            update: function(current_percentage) { $('#m-callback-update').html(current_percentage); },
            done: function() { $('#m-callback-done').html('yeah! done!'); }
        });
    });
    $('#m-callback-reset').click(function() {
        $('#m-callback-update').html('');
        $('#m-callback-done').html('');
        $('.m-callback .progress-bar').attr('data-transitiongoal', 0).progressbar({
            update: function(current_percentage) { $('#m-callback-update').html(current_percentage); },
            done: function() { $('#m-callback-done').html('yeah! done!'); }
        });
    });
});