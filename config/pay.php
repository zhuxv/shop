<?php

return [
    'alipay' => [
        'app_id'         => '2021000117674296',
        'ali_public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAiN6KrVxBJrt9ZBDE0+lDwTuPoaloCDBi/hPZwcy5YUKdaRY4rllkRong6I84ncgJivUzfkUaPV8uGpI02i3NzVP8c3eJDf17fAxT3w5jRRulH391V+vg24GC5L+xJRSamuJnlLEdzKDCYaUdGmmwzZwl5OcmQG00CJBAMNzwFDe87wBdR/8oVbMNkr8b9qxnIi+6739gUBS1zVaA5VEfE+XOtiFwcVaSawXyfsade34D+NpDFdm1NGiBnvt5+sm8d3Fs+MNAf70u4kcSqI5sGoC6PWihdg+Twqdi41NSzsdUhc1wSQjOgzed1rpl6aXCszLs7hzNEMtU3Olg8i2AlwIDAQAB',
        'private_key'    => 'MIIEpAIBAAKCAQEAjiKU4dIuFtGn9J2blFkUSlM8AcINTE5z++JZljSQANduoBohSVtj7Z1r65jtp9VDgORXw02F4u3CmtpZXtn4jw32WVrsk7XkDmzvuoiUk/MQv0iCyNUCgxSofF5xbKZK7HWkag2Tle/NIU9164oFipSeedXlZtdRtYDrR/wUOck/kHtfQSDyX1NNxGfndN0blzXnF6alvpQqcKALeGnMbvarQ0kWu59W46qjL9KOWRawr4/y5HZhqGEpfW7Ohw3xd/jTg/8tLkeMazdy10LTW4/aE9ql4uhHyvMVAncOlugEvs35NXH/QTJ9YpJVnIfts2i6PeXLjS+u4CeJBeJuPwIDAQABAoIBAG8Hzpu1LzG+J2Cr+cvh5jrFWHvbFb5FDs92Gxt+BfvSTmWc4+HFFYOZ1KMfPI89xlSai4BSP8Nsjfefl+7DgSpm8skMgQBGN1eZe4+Qf9gvKSbzws6VrzesXI2CzUHdiWu73mrGHkMjJ2/c/bW4hOmjfUSZdXFnxDlv8tuxrC8SteYMwpNH6yHPxEEmwjfLBB/x51EyDm/1GcppUJ2HKPOEstOh4oDJJzgSdeWpQLeJlE37x3LWVTz6fCvxINQKj+dDQa35u+pYC6WjtObB+HdnNfGMeU8XOgf5NSUyQe7zwrXGKxrObMOoTuJPo/e7Lx2VbmYn1M6ENvEIA8SVRPECgYEA9iM6GdmgjH+nJs3JVt0kFd6GerAws8EPScoB6z8atfLgrJn9P+cExkaqGsVRhijXacwJb1TzooXY0AVFYcJBmYKUIMqn9oP4IKud2NeURMyh7PoanJLwFNni1MYLnGVxIQLZWDQGFuy8HmdfzMPyhHDq2BoyMylvXs8Cp85lqjcCgYEAk9SKsK34kU8vEdTqgWDgT8TTh9nwpir3PrZkyP8CZd/DLMoGoHJiyhfAK/dlpHWz1YZe9Tf2mPl//3fs4Ad2eHFPRXZP42v4JwSvWyFbFSUEAvgLdZ7BKbrRJi+7QNKFjDaF7cDQA7PC5pn0jVbwsiNUuXPabJvPxFjbNWRYuDkCgYEAuHn4ju1Ublkyj1vHHmqKJDXu9r9dESyOZ9CWlZlrTJlnmRWlAKMGKhFGZuAi1PmBUhMRszapj9LfiDGbKcTtY7/Bg75AGvwYGWxm1uHkh5gTeMiO73EVrZsMbkqs4yAIpSQ8f+Yl9kKiT+tMmuz1tBvpd+RSYZQZm6ZtqBWCjwsCgYEAhtOGWnWRCxZpG55Q8wbkDly5gGGpNiRhs3SunxLVoQxf+e2X9aXdq+vVfUP6E/C1v7z5xjTwV7zWnK1IAVtNFbRiVDv/yK+keBGxzS+y3qoP6pVH/lJ4YhLcxjMqWYin/KWNqLX+AiJlU+R+QppUlGPc1fdv8zZ4W9+erieDMyECgYAzHPyfFdjvYDP70yU/24cJof0wozwssZcxnto6tZXU3bnOsoQxQuMg0Jisn1Y7nJwGG9924Da7asXcQjAexxhBsMv+5znIhvuFIFuxYaa08Car4LgqO8znzfq86+iZCD6/go42atQCLhKlqprhr0h9LGKOqCkZpf/aeKE3ag7Gbg==',
        'log'            => [
            'file' => storage_path('logs/alipay.log'),
        ],
    ],

    'wechat' => [
        'app_id'      => '',
        'mch_id'      => '',
        'key'         => '',
        'cert_client' => '',
        'cert_key'    => '',
        'log'         => [
            'file' => storage_path('logs/wechat_pay.log'),
        ],
    ],
];