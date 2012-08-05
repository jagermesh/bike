// 
// Breeze Framework : Version 0.0.5
// (C) Sergiy Lavryk
// jagermesh@gmail.com
// 

!function ($, window, undefined) {

  $(document).ready(function() { 
    
    var users = br.dataSource(br.baseUrl + 'api/users/');

    users.on('error', function(operation, error) {
      br.growlError(error);
    });

    $('.action-signup').click(function() {

      var form = $(this).closest('form');
      var data = {};
      $(form).find('input').each(function() {
        data[$(this).attr('name')] = $(this).val();
      });
      $(form).find('select').each(function() {
        data[$(this).attr('name')] = $(this).val();
      });
      users.invoke('signup', data, function(result) {
        if (result) {
          br.redirect('?from=signup');
        }
      });

    });

    $('.action-login').click(function() {

      var form = $(this).closest('form');
      var data = {};
      $(form).find('input').each(function() {
        data[$(this).attr('name')] = $(this).val();
      });
      $(form).find('select').each(function() {
        data[$(this).attr('name')] = $(this).val();
      });

      users.invoke( 'login'
                  , data
                  , function(result) {
                      if (result) {
                        br.redirect(br.request.get('caller', '?from=login'));
                      }
                    }
                  );

    });

    $('.action-forgot-password').click(function() {

      var form = $(this).closest('form');
      var data = {};
      $(form).find('input').each(function() {
        data[$(this).attr('name')] = $(this).val();
      });
      $(form).find('select').each(function() {
        data[$(this).attr('name')] = $(this).val();
      });

      users.invoke( 'remindPassword'
                  , data
                  , function(result) {
                      if (result) {
                        br.redirect(br.request.get('caller', '?from=login'));
                      }
                    }
                  );

    });

    $('.action-logout').live('click', function() {

      users.invoke( 'logout'
                  , { }
                  , function(result) {
                      if (result) {
                        document.location = br.baseUrl;
                      }
                    }
                  );

    });

  });

}(jQuery, window);
