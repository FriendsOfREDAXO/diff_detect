<?php

namespace FriendsOfRedaxo\DiffDetect;

/**
 * Linking web page as one file after saving as "Web Page, complete" in a browser.
 *
 * @author earthperson <ponomarev.dev@gmail.com>
 * @author xong <robert.rupf@maumha.de>
 */
class HtmlOnepage
{
    protected array $parsedUrl = [];
    protected ?string $base = null;
    protected string $content = '';
    protected bool $isProcessed = false;

    public
    function __construct(
        $url,
        $content
    ) {
        $parsedUrl = parse_url($url);
        $this->parsedUrl = $parsedUrl;
        $this->content = $content;

        if (preg_match('~<base[^>]+href="([^"]+)"[^>]*>~ism', $content, $match)) {
            $this->base = $match[1];
        }
    }

    public
    function get(): string
    {
        $this->process();
        return $this->content;
    }

    protected function process(): void
    {
        if ($this->isProcessed) {
            return;
        }

        $this->dataUriPage();
        $this->content = $this->dataUriResources($this->content);
        $this->linkCss();
        $this->linkJavaScript();
        $this->isProcessed = true;
    }

    /**
     * replace all images
     */
    protected
    function dataUriPage(): void
    {
        $this->content = preg_replace_callback(
            '~(<img[^>]+?src=["\'])(.*?)(["\'][^>]*>)~ism',
            fn($match) => $match[1].$this->dataUri($match[2]).$match[3],
            $this->content
        );
    }

    // convert url to data uri
    private
    function dataUri(
        $url
    ): string {
        $socket = \rex_socket::factoryUrl($this->getUrl($url));
        $socket->followRedirects(3);
        $response = $socket->doGet();
        if ($response->isOk()) {
            return 'data:'.$response->getHeader('Content-Type').';base64,'.base64_encode($response->getBody());
        }

        // transparent 1px×1px gif
        return 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
    }

    private function getUrl($url): string
    {
        $url = htmlspecialchars_decode(trim($url, '"\' '));

        if (0 === strpos($url, '//')) {
            return $this->parsedUrl['scheme'].':'.$url;
        }

        if (str_starts_with($url, 'https://') or str_starts_with($url, 'http://')) {
            return $url;
        }

        if ($this->base) {
            return rtrim($this->base, '/').'/'.ltrim($url, '/');
        }

        if (str_starts_with($url, '/')) {
            return $this->parsedUrl['scheme'].'://'.$this->parsedUrl['host'].$url;
        } else {
            return $this->parsedUrl['scheme'].'://'.$this->parsedUrl['host'].$this->parsedUrl['path'].'/'.$url;
        }
    }

    protected
    function dataUriResources(
        $content
    ): string {
        return preg_replace_callback(
            '~(\s+background:\s*url\()(.*?)(\))~is',
            fn($match) => $match[1].$this->dataUri($match[2]).$match[3],
            $content
        );
    }

    protected
    function linkCss()
    {
        $this->content = preg_replace_callback(
            '~(<link[^>]+?href=[\"\'])(.*?)([\"\'][^>]*>)~ism',
            function ($match) {
                if (!str_contains($match[1], 'rel="stylesheet"') and !str_contains($match[1], 'rel=\'stylesheet\'') and !str_contains($match[3], 'rel="stylesheet"') and !str_contains($match[3], 'rel=\'stylesheet\'')) {
                    return $match[0];
                }

                $socket = \rex_socket::factoryUrl($this->getUrl($match[2]));
                $socket->followRedirects(3);
                $response = $socket->doGet();
                if ($response->isOk()) {
                    return '<style type="text/css">/* <![CDATA[*/'.$this->dataUriResources(
                            $response->getBody()
                        ).'/*]]>*/</style>';
                }

                return $match[0];
            },
            $this->content
        );
    }

    protected
    function linkJavaScript()
    {
        $this->content = preg_replace_callback(
            '~(<script[^>]+?src=[\"\'])(.*?)([\"\'][^>]*>\s*</script>)~is',
            function ($match) {
                return '';
                // $socket = \rex_socket::factoryUrl($this->getUrl($match[2]));
                // $socket->followRedirects(3);
                // $response = $socket->doGet();
                // if ($response->isOk()) {
                //     return '<script type="text/javascript">/* <![CDATA[*/'.$this->dataUriResources(
                //             $response->getBody()
                //         ).'/*]]>*/</script>';
                // }
                //
                // return $match[0];
            },
            $this->content);
    }
}
