<?php

/**
 * X Ads Mini Composer configuration.
 *
 * IMPORTANT:
 * - This tool is intended to run locally.
 * - Do not expose it to the public internet.
 * - account_id, user_id, bearer, and ct0 are the values used by the browser-console script.
 * - Paste Cookie if X returns "Could not authenticate you" / 401 / 403.
 */
return [
    'api_version' => '11',

    // Your X Ads account and promotable user.
    'account_id' => '18ce55nu7l7',
    'user_id' => '1855582736',

    // Schedule Tweets this many minutes ahead.
    'schedule_after_minutes' => 1,

    // Bearer used by your current working browser request.
    // If X rotates it later, replace it here.
    'bearer' => 'AAAAAAAAAAAAAAAAAAAAAPnA9gAAAAAAZHpqKYoDdMCaqTUBktzAdK38BGk=LNsI9r2BHSjZ7cl5wD6Sh6NhxwZd2j8lXDSd6GDoQVYBlzx5Ff',

    /**
     * Paste the COMPLETE Cookie request header here if X returns 401/403.
     *
     * Example:
     * 'cookie' => 'auth_token=...; ct0=...; twid=...; ...',
     *
     * Easiest way:
     * 1. Open ads.x.com while logged in.
     * 2. DevTools -> Network.
     * 3. Open a request to ads-api.x.com.
     * 4. Request Headers -> Cookie -> copy the whole value.
     */
    'cookie' => 'kdt=sBHTw9eFLv0wLrptuamPaZfFWoLTMmv1BGWLkRSL; _twpid=tw.1772506211191.631514404378000472; cf_clearance=jvUQLiOcMmzaIql_9d0SZdbo2p.WwIc_G6oYFgcQHqw-1781157233-1.2.1.1-PWbEXz61qJddsacb9kYLkfU_XCAC8sLVIH9rYbsNa7NN6EeL6FWzji_2fCpZMppXirtemkHZFLB0Hey5VkXz1RCM0qgae6efQakUgcz.8IP18plMB7ACJ_d0F5oKA8uqPGEFCcjvFXcwWXI9XnZ_VIDlgDcpi_pNs3hE99ui2nASypMCvvi4iFuNkcNmW9EUnoyfvK7lweK2xcLupw9oLMcMfFqvacF8mB7nu5oGBLnaWWi57DRlQlwfjolphNoVHXESnTxNanaIgcgvrRSK4KscUVDaYhCTAxcbj.ESr7Hcp71ML3GY47x8vpYjS_0_HOyyb69BL.2dZgDcndb68A; __cuid=6366a358339c42ebac8672d59fd1a810; __gads=ID=db32e5272d677c52:T=1786609772:RT=1786609772:S=ALNI_MaIRe8uRlAE85NO-XWtNEDa96ruuA; __gpi=UID=000014dd7b5ab7cf:T=1786609772:RT=1786609772:S=ALNI_Ma5-zwp2jMq6n_24Mw8RA0TUqB0Pw; __eoi=ID=48932a396c69aed6:T=1786609772:RT=1786609772:S=AA-Afjb6BIL_FdzkzGthIBosZzBh; personalization_id="v1_rbaCDSGHwNHikQ+AFO8PqQ=="; _ga=GA1.1.565548468.1786611133; dnt=1; ads_prefs="HBISAAA="; guest_id_ads=v1%3A178661130677112145; guest_id_marketing=v1%3A178661130677112145; guest_id=v1%3A178661130677112145; external_referer=padhuUp37zjgzgv1mFWxJ12Ozwit7owX|0|8e8t2xd8A2w%3D; twid=u%3D1855582736; auth_multi="262720894:609aae8e1d3fd8ced56ff3809ec53105071ff674|379165917:26a0b1d206f171c9e60a77a0ea7dae1618314fa5|1399759676271730688:b5c02e0424d0e21bfb679299d191e79ad59fb96a"; auth_token=e60d45ee06e22113797807bb5c2ba860ade5cbcf; ct0=f9376ac55c5556073929e0f4a13d2e24d3dd29f9a99dffdfc96d5acfd6bcbcda6f455aa8e44d6f3edaef690a7397ee736aaf1a396e9c4b31b1246ac173d55c2c20e0190387f69cc1242113d97d20bb9e; _ga_BLY4P7T5KW=GS2.1.s1786676633$o2$g1$t1786677111$j60$l0$h0; _gcl_au=1.1.488247480.1786612997.-.-.1786677143.1003763428.1786677143.1786677203; __cf_bm=f.2XI1FLwT7kQLAdH7q_4K6laP1T1H25_ehB2PAbeik-1786679942.6454618-1.0.1.1-qdFEF237Z6OFglXYQpF4w141Ieyd8FEwwDRaEOO320gAUpcWvJx2QcJ__k01DmpKoYb_Xi8zNn_oQ4z1seRxMCbiJLO8dqGvABmMYAC9aS7N.KkKQMYSgmV9RUTi7Pdy; _ga_BT3WQMJWD7=GS2.1.s1786680275$o3$g1$t1786680277$j58$l1$h1069828206',

    // CSRF token used by the working browser-console script.
    'ct0' => 'f9376ac55c5556073929e0f4a13d2e24d3dd29f9a99dffdfc96d5acfd6bcbcda6f455aa8e44d6f3edaef690a7397ee736aaf1a396e9c4b31b1246ac173d55c2c20e0190387f69cc1242113d97d20bb9e',

    // Request timeout in seconds.
    'timeout' => 30,
];
