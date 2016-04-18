# BrainyQuote.com Crawler

To run the crawler all jobs sequentially,

php artisan crawl:all

To run different parts one after another,

a) To get new authors,

php artisan crawl:new-author

b) To mark authors as popular,

php artisan crawl:popular-author

c) To crawl all authors from 'authors' table,

php artisan crawl:author

This can be called also with,

php artisan crawl:author --author_name='Alvar Aalto'

php artisan crawl:author --author_url='/quotes/authors/w/willie_aames.html'

d) To crawl all popular topics,

php artisan crawl:popular-topic

e) To crawl keyword ranges,

php artisan crawl:keyword-range

f) To crawl topics from 'topics' table,

php artisan crawl:topic

This can be called also with,

php artisan crawl:topic --topic_name='Age'

php artisan crawl:topic --topic_url='/quotes/topics/topic_amazing.html'

g) To get keywords from keyword_ranges table,

php artisan crawl:get-keyword

h) To crawl 'keywords' from keywords table,

php artisan crawl:keyword

This can be also called with,

php artisan crawl:keyword --keyword_name='ABBA'

php artisan crawl:keyword --keyword_url='/quotes/keywords/abandon.html'

i) To crawl all quotes from 'quotes' table,

php artisan crawl:parse-quote

All commands can be done with --cleanup flag to clean relative database section before crawling.