<?php

declare(strict_types=1);

namespace Cantao\SolaxBundle\Controller\FrontendModule;

use Cantao\SolaxBundle\Service\SolaxDataResolver;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\ModuleModel;
use Contao\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_scope' => 'frontend', '_token_check' => true])]
class SolaxMetricsController extends AbstractFrontendModuleController
{
    public const TYPE = 'solax_metrics';

    public function __construct(private readonly SolaxDataResolver $resolver)
    {
    }

    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $resolved = $this->resolver->resolve();

        $template->metrics = $resolved['metrics'];
        $template->solaxSource = $resolved['source'];
        $template->solaxTimestamp = $resolved['timestamp'];

        return $template->getResponse();
    }
}
