"""
Dashboard menu helper for the e2e tests (theme 0.17.0).
The menu lists groups; the pages of a group are tabs at the top of the page.
menu(pg) returns the text and links of the whole menu: the side menu, the
"Publish a listing" button and the tabs of every group. The page is left where it was.
"""
def menu(pg):
    back = pg.url
    text = [pg.inner_text('.sm-dash__side')]
    links = pg.eval_on_selector_all('.sm-dash__side a', 'as => as.map(a => a.href)')
    for href in [h for h in links if 'action=logout' not in h]:
        pg.goto(href)
        if pg.locator('.sm-dash__tabs').count():
            text.append(pg.inner_text('.sm-dash__tabs'))
            links += pg.eval_on_selector_all('.sm-dash__tabs a', 'as => as.map(a => a.href)')
    pg.goto(back)
    return (' '.join(text) + ' ' + ' '.join(links)).replace(' ', ' ').replace('\xa0', ' ')
