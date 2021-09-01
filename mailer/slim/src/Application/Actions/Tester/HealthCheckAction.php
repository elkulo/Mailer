<?php
declare(strict_types=1);

namespace App\Application\Actions\Tester;

use App\Application\Settings\SettingsInterface;
use App\Application\Actions\Action;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;

class HealthCheckAction extends Action
{

    /**
     * @var SettingsInterface
     */
    protected $settings;

    /**
     * @var Twig
     */
    protected $view;

    /**
     * @param LoggerInterface $logger
     * @param SettingsInterface $settings
     * @param Twig $view
     */
    public function __construct(LoggerInterface $logger, SettingsInterface $settings, Twig $view)
    {
        parent::__construct($logger);
        $this->settings = $settings;
        $this->view = $view;
    }

    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {

        $this->logger->info('Tester was viewed.');

        $view = $this->view;
        $settings = $this->settings;

        // bodyを生成
        $response = $view->render($this->response, 'pages/tester.twig', [
            //'title' => $settings->get('site.title'),
            //'description' => $settings->get('site.description'),
            //'robots' => $settings->get('site.robots')
        ]);

        return $response;
    }
}
