<?php
/**
 * This file is part of d5whub extend benchmark
 * @author Vitor Reis <vitor@d5w.com.br>
 */

declare(strict_types=1);

namespace D5WHUB\Extend\Benchmark\Printer;

use D5WHUB\Extend\Benchmark\Utils\Printer;
use D5WHUB\Extend\Benchmark\Utils\Status;

class Html implements Printer
{
    private function flush(string ...$values): void
    {
        foreach ($values as $value) {
            echo $value;
            @ob_flush();
            @flush();
        }
    }

    public function withTime(string ...$values): self
    {
        foreach ($values as $value) {
            $this->flush("<span class='m0 m90'>[" . date('Y-m-d H:i:s') . "] $value</span>");
        }
        return $this;
    }

    public function start(): self
    {
        @ini_set('output_buffering', 'off');
        @ini_set('zlib.output_compression', false);
        while (@ob_end_flush());
        @ini_set('implicit_flush', true);
        @ob_implicit_flush();
        @header('Content-Type: text/html; charset=UTF-8');

        $this->flush(<<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<title>D5WHUB Extend Benchmark</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
*{font-size:14px;font-family:Consolas,monospace;background:#111;color:#DDD}
.m0{font-weight:400}
.m1{font-weight:bold}
.m3{font-style:italic}
.m90{color:#777}
.m33{color:#f88d00}
.m31{color:#f80000}
.m36{color:#00b6f8}
</style>
</head>
<body>
HTML
        );
        $this->withTime("<span class='m3 m90'>D5WHUB Extend Benchmark</span><br>");
        $this->skipline();
        return $this;
    }

    public function title(string $title, ?string $comment): self
    {
        $title = trim($title);
        $this->withTime("<span class='m0 m1 m3'>$title</span><br>");
        if ($comment && $comment = trim($comment)) {
            $this->withTime("<span class='m0 m1 m3'>$comment</span><br>");
        }
        return $this;
    }

    public function skipline(int $times = 1): self
    {
        $this->withTime(...array_fill(1, $times, "<br>"));
        return $this;
    }

    public function subtitle(string $title, ?string $comment = null, ?int $iterations = null): self
    {
        $title = trim($title);
        $comment = $comment ? trim($comment) : '';
        $iterations = $iterations ? (" $iterations time" . ($iterations > 1 ? 's' : '')) : "";
        $comment = $comment ? " " . ($iterations ? "- " : "") . "$comment" : "";

        $this->withTime("<span class='m36'>• $title</span><span class='m90'>$iterations$comment</span><br>");
        return $this;
    }

    public function tmpwrite(string $text): self
    {
        $text = trim($text);
        $this->withTime("<span class='m90 tmp'>$text</span>");
        return $this;
    }

    public function tmpclear(): self
    {
        $this->flush("<script>document.querySelectorAll('.tmp').forEach(e => e.parentNode.remove())</script>");
        return $this;
    }

    public function results(array $results, bool $end = false): self
    {
        $pad = max(array_map('strlen', array_map('trim', array_keys($results))));
        $best = current($results)['_']['average'];

        foreach ($results as $title => $result) {
            $title = str_replace(' ', "&nbsp;", str_pad($title, $pad));

            $text = '';

            switch ($result['_']['status']) {
                case Status::SUCCESS:
                    if ($best === $result['_']['average']) {
                        $text = sprintf(
                            "<span class='m0'>| %s | %.11fs | baseline</span>",
                            $title,
                            $result['_']['average']
                        );
                    } else {
                        $slower = round((1 - $result['_']['average'] / $best) * 100 * -1, 1);
                        $text = sprintf(
                            "<span class='m0'>| %s | %.11fs | %s (+%.11fs)</span>",
                            $title,
                            $result['_']['average'],
                            "$slower% slower",
                            $result['_']['average'] - $best
                        );
                    }
                    break;

                case Status::SKIPPED:
                    $text = $end
                        ? sprintf("<span class='m0'>| %s | </span><span class='m3 m90'>Not conclusive</span>", $title)
                        : sprintf(
                            "<span class='m0'>| %s | </span><span class='m3 m90'>%s</span>",
                            $title,
                            current($result['_']['error'])
                        );
                    break;

                case Status::PARTIAL:
                    $text = $end
                        ? sprintf("<span class='m0'>| %s | </span><span class='m3 m90'>Not conclusive</span>", $title)
                        : sprintf(
                            "<span class='m0'>| %s | %.11fs | "
                            . "<span class='m3 m33'>Partial success %s/%s, failed: %s</span>",
                            $title,
                            $result['_']['average'],
                            count(array_filter(array_slice($result, 1), fn($i) => $i['status'] === Status::SUCCESS)),
                            count(array_slice($result, 1)),
                            current($result['_']['error'])
                        );
                    break;

                case Status::FAILED:
                    $text = $end
                        ? sprintf("<span class='m0'>| %s | </span><span class='m3 m90'>Not conclusive</span>", $title)
                        : sprintf(
                            "<span class='m0'>| %s | </span><span class='m3 m31'>Failed: %s",
                            $title,
                            current($result['_']['error'])
                        );
                    break;
            }
            $this->withTime("$text<br>");
        }
        return $this;
    }

    public function end(float $runningTime, int $totalBenchmark, int $totalInteractions): self
    {
        $this->withTime(sprintf(
            "<span class='m3 m90'>End %.11fs, %d benchmark%s and %d interaction%s</span><br>",
            $runningTime,
            $totalBenchmark,
            $totalBenchmark > 1 ? 's' : '',
            $totalInteractions,
            $totalInteractions > 1 ? 's' : ''
        ));
        $this->flush(<<<HTML
</body>
</html>
HTML
        );
        return $this;
    }
}
