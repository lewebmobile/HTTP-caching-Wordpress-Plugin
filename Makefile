languages/%.mo: languages/%.po
	msgfmt -o $@ $^

languages/lwm_http_caching.po: wp-http-cache.php
	xgettext -p languages -d lwm_http_caching --from-code utf-8 --keyword=__ --keyword=_e wp-http-cache.php