<?php

return array (
  'MAILER' => 'smtp                # Mail driver: smtp, sendmail, mailgun, ses, etc.',
  'HOST' => 'smtp.mailtrap.io',
  'PORT' => '2525',
  'USERNAME' => 'null',
  'PASSWORD' => 'null',
  'ENCRYPTION' => 'null            # ssl, tls',
  'FROM' => '"${APP_NAME}"',
  'REPLY' => 'no-reply@your-domain.com   # Default reply-to address',
  'CC' => 'null                    # Default CC addresses (comma-separated)',
  'BCC' => 'null                   # Default BCC addresses (comma-separated)',
  'QUEUE' => 'true                 # Queue emails for deferred sending (true/false)',
  'LOG' => 'false          # Enable/disable email logging',
);
