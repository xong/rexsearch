<?php
$stats = new RexSearchStats();
$REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['maxtopSearchitems'] = rex_request('count','int',10);
a587_stats_saveSettings($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']);

echo json_encode($stats->getTopSearchterms($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['maxtopSearchitems'], rex_request('only','int',0)));
