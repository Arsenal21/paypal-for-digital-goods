#!/bin/bash
find ./paypal-for-digital-goods/ -type f \( -name '*.php' \) | xargs xgettext --language=PHP --from-code=UTF-8 --sort-by-file --omit-header --keyword=_ --keyword=gettext --keyword=dgettext:2 --keyword=dcgettext:2 --keyword=ngettext:1,2 --keyword=dngettext:2,3 --keyword=dcngettext:2,3 --keyword=__ --keyword=___:1 --keyword=___:1,2c --keyword=__ngettext:1,2 --keyword=__ngettext_noop:1,2 --keyword=_c --keyword=_e --keyword=_ex:1,2c --keyword=_n --keyword=_n:1,2 --keyword=_n_noop --keyword=_n_noop:1,2 --keyword=_nc:1,2 --keyword=_nx --keyword=_nx:1,2,4c --keyword=_nx:4c,1,2 --keyword=_nx_noop --keyword=_nx_noop:1,2,3c --keyword=_nx_noop:4c,1,2 --keyword=_refresh --keyword=_x --keyword=_x:1,2c --keyword=append --keyword=esc_attr_ --keyword=esc_attr__ --keyword=esc_attr_e --keyword=esc_attr_x --keyword=esc_attr_x:1,2c --keyword=esc_html__ --keyword=esc_html_e --keyword=esc_html_x --keyword=esc_html_x:1,2c --keyword=n___:1,2 --keyword=n___:1,2,4c --keyword=prepend --keyword=setLabel --keyword=setLegend --keyword=setMessage --keyword=setValue --keyword=T_pgettext:1c,2 --keyword=translate --output="./paypal-for-digital-goods/languages/paypal-express-checkout.pot"