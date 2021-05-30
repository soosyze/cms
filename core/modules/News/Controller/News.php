<?php

declare(strict_types=1);

namespace SoosyzeCore\News\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Soosyze\Components\Http\Response;
use Soosyze\Components\Http\Stream;
use Soosyze\Components\Paginate\Paginator;

class News extends \Soosyze\Controller
{
    /**
     * @var int
     */
    public static $limit;

    /**
     * @var int
     */
    private $dateCurrent;

    /**
     * @var int
     */
    private $dateNext;

    /**
     * @var string
     */
    private $titleMain;

    /**
     * @var string
     */
    private $link;

    public function __construct()
    {
        $this->pathServices = dirname(__DIR__) . '/Config/services.php';
        $this->pathRoutes   = dirname(__DIR__) . '/Config/routes.php';
        $this->pathViews    = dirname(__DIR__) . '/Views/';
    }

    public function index(ServerRequestInterface $req): ResponseInterface
    {
        return $this->page(1, $req);
    }

    public function page(int $page, ServerRequestInterface $req): ResponseInterface
    {
        self::$limit = self::config()->get('settings.news_pagination', 6);

        $query = self::query()
            ->from('node')
            ->where('node_status_id', '=', 1)
            ->where('type', '=', 'article')
            ->orderBy('sticky', SORT_DESC)
            ->orderBy('date_created', SORT_DESC)
            ->limit(self::$limit, self::$limit * ($page - 1))
            ->fetchAll();

        if ($page !== 1 && !$query) {
            return $this->get404($req);
        }

        $default = $query
            ? ''
            : t('No articles for the moment');

        foreach ($query as &$value) {
            $value[ 'field' ] = self::node()->makeFieldsById('article', $value[ 'entity_id' ]);

            $alias = self::alias()->getAlias('node/' . $value[ 'id' ], 'node/' . $value[ 'id' ]);

            $value[ 'link_view' ] = self::router()->makeRoute($alias);
        }
        unset($value);

        $queryAll = self::query()
            ->from('node')
            ->where('node_status_id', '=', 1)
            ->where('type', '=', 'article')
            ->fetchAll();

        $link = self::router()->getRoute('news.page', [], false);

        return self::template()
                ->getTheme('theme')
                ->view('page', [
                    'title_main' => t(self::config()->get('settings.new_title'))
                ])
                ->make('page.content', 'news/content-news-index.php', $this->pathViews, [
                    'default'  => $default,
                    'news'     => $query,
                    'link_rss' => self::router()->getRoute('news.rss'),
                    'paginate' => new Paginator(count($queryAll), self::$limit, $page, $link)
        ]);
    }

    public function viewYears(string $years, string $page, ServerRequestInterface $req): ResponseInterface
    {
        $date              = '01/01/' . $years;
        $this->dateCurrent = strtotime($date);
        $this->dateNext    = strtotime($date . ' +1 year -1 seconds');
        $this->titleMain   = t('Articles from :date', [ ':date' => $years ]);
        $this->link        = self::router()->getRoute('news.years.page', [ ':year' => $years ], false);

        return $this->renderNews($page, $req);
    }

    public function viewMonth(string $years, string $month, string $page, ServerRequestInterface $req): ResponseInterface
    {
        $date              = $month . '/01/' . $years;
        $this->dateCurrent = strtotime($date);
        $this->dateNext    = strtotime($date . ' +1 month -1 seconds');
        $this->titleMain   = t('Articles from :date', [ ':date' => strftime('%B %Y', $this->dateCurrent) ]);
        $this->link        = self::router()->getRoute('news.month.page', [
            ':year'  => $years,
            ':month' => $month
            ], false);

        return $this->renderNews($page, $req);
    }

    public function viewDay(string $years, string $month, string $day, string $page, ServerRequestInterface $req): ResponseInterface
    {
        $date              = $month . '/' . $day . '/' . $years;
        $this->dateCurrent = strtotime($date);
        $this->dateNext    = strtotime($date . ' +1 day -1 seconds');
        $this->titleMain   = t('Articles from :date', [ ':date' => strftime('%d %B %Y', $this->dateCurrent) ]);
        $this->link        = self::router()->getRoute('news.day.page', [
            ':year'  => $years,
            ':month' => $month,
            ':day'   => $day
            ], false);

        return $this->renderNews($page, $req);
    }

    public function viewRss(ServerRequestInterface $req): ResponseInterface
    {
        self::$limit = self::config()->get('settings.news_pagination', 6);

        $items = self::query()
            ->from('node')
            ->where('node_status_id', '=', 1)
            ->where('type', '=', 'article')
            ->orderBy('date_created', SORT_DESC)
            ->limit(self::$limit)
            ->fetchAll();

        foreach ($items as &$item) {
            $item[ 'field' ] = self::node()->makeFieldsById('article', $item[ 'entity_id' ]);

            $alias = self::alias()->getAlias('node/' . $item[ 'id' ], 'node/' . $item[ 'id' ]);

            $item[ 'link' ] = self::router()->makeRoute($alias);
        }
        unset($item);

        $lastBuildDate = isset($items[ 0 ][ 'date_created' ])
            ? $items[ 0 ][ 'date_created' ]
            : '';

        $stream = new Stream(
            self::template()
                ->createBlock('news/page-news-rss.php', $this->pathViews)
                ->addVars([
                    'description'   => self::config()->get('settings.meta_description', ''),
                    'items'         => $items,
                    'language'      => self::config()->get('settings.lang', 'en'),
                    'lastBuildDate' => $lastBuildDate,
                    'link'          => self::router()->getBasePath(),
                    'title'         => self::config()->get('settings.meta_title', ''),
                    'xml'           => '<?xml version="1.0" encoding="UTF-8" ?>'
                ])
        );

        return (new Response(200, $stream))
                ->withHeader('content-type', 'application/octet-stream')
                ->withHeader('content-length', $stream->getSize())
                ->withHeader('content-disposition', 'attachment; filename=rss.xml')
                ->withHeader('pragma', 'no-cache')
                ->withHeader('cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0')
                ->withHeader('expires', '0');
    }

    private function renderNews(string $page, ServerRequestInterface $req): ResponseInterface
    {
        $page = empty($page)
            ? 1
            : substr(strrchr($page, '/'), 1);

        self::$limit = self::config()->get('settings.news_pagination', 6);

        $offset = self::$limit * ($page - 1);
        $news   = $this->getNews($this->dateCurrent, $this->dateNext, $offset);

        $isCurrent = (time() >= $this->dateCurrent && time() <= $this->dateNext);

        $default = t('No articles for the moment');
        if (!$news && !($page == 1 && $isCurrent)) {
            return $this->get404($req);
        }

        foreach ($news as &$new) {
            $new[ 'field' ] = self::node()->makeFieldsById('article', $new[ 'entity_id' ]);

            $alias = self::alias()->getAlias('node/' . $new[ 'id' ], 'node/' . $new[ 'id' ]);

            $new[ 'link_view' ] = self::router()->makeRoute($alias);
        }
        unset($new);

        $nodesAll = $this->getNewsAll($this->dateCurrent, $this->dateNext);

        return self::template()
                ->getTheme('theme')
                ->view('page', [
                    'title_main' => $this->titleMain
                ])
                ->make('page.content', 'news/content-news-index.php', $this->pathViews, [
                    'news'     => $news,
                    'paginate' => new Paginator(count($nodesAll), self::$limit, $page, $this->link),
                    'default'  => $default,
                    'link_rss' => self::router()->getRoute('news.rss')
        ]);
    }

    private function getNews(int $dateCurrent, int $dateNext, int $offset = 0): array
    {
        return self::query()
                ->from('node')
                ->where('type', '=', 'article')
                ->between('date_created', (string) $dateCurrent, (string) $dateNext)
                ->where('node_status_id', '=', 1)
                ->orderBy('sticky', SORT_DESC)
                ->orderBy('date_created', SORT_DESC)
                ->limit(self::$limit, $offset)
                ->fetchAll();
    }

    private function getNewsAll(int $dateCurrent, int $dateNext): array
    {
        return self::query()
                ->from('node')
                ->where('type', '=', 'article')
                ->between('date_created', (string) $dateCurrent, (string) $dateNext)
                ->where('node_status_id', '=', 1)
                ->fetchAll();
    }
}
