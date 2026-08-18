<?php

declare(strict_types=1);

namespace {
    if (!class_exists(\Contao\CoreBundle\DataContainer\RecordLabel::class)) {
        eval(<<<'PHP'
namespace Contao\CoreBundle\DataContainer;

final class RecordLabel
{
    public ?array $columns = null;
    public ?string $htmlLabel = null;
    public ?array $htmlColumns = null;
    public ?string $htmlPreview = null;
    public ?string $state = null;

    public function __construct(public string $label)
    {
    }

    public static function fromHtml(string $html): self
    {
        $label = new self(trim(strip_tags($html)));
        $label->htmlLabel = $html;

        return $label;
    }
}
PHP);
    }
}

namespace Diversworld\ContaoEditorialWorkflow\Tests\EventListener\DataContainer {
    use Diversworld\ContaoEditorialWorkflow\EventListener\DataContainer\WorkflowFieldsListener;
    use Diversworld\ContaoEditorialWorkflow\Workflow\WorkflowManager;
    use Diversworld\ContaoEditorialWorkflow\Workflow\WorkflowStatus;
    use PHPUnit\Framework\TestCase;
    use ReflectionMethod;
    use Symfony\Component\DependencyInjection\ContainerInterface;

    class WorkflowFieldsListenerTest extends TestCase
    {
        public function testAppendsStatusToStringLabels(): void
        {
            $listener = $this->createListener();

            $label = $this->invokePrivateMethod($listener, 'appendStatusToLabel', [
                'News label',
                ['workflow_status' => WorkflowStatus::STATUS_DRAFT],
            ]);

            $this->assertSame('News label <span class="tl_gray">[Entwurf]</span>', $label);
        }

        public function testAppendsStatusToArrayLabels(): void
        {
            $listener = $this->createListener();

            $label = $this->invokePrivateMethod($listener, 'appendStatusToLabel', [
                ['Newsletter', '<p>Preview</p>', 'published'],
                ['workflow_status' => WorkflowStatus::STATUS_REVIEW],
            ]);

            $this->assertSame(
                ['Newsletter <span class="tl_gray">[In Prüfung]</span>', '<p>Preview</p>', 'published'],
                $label
            );
        }

        public function testAppendsStatusToContaoSixRecordLabelsWithoutLosingHtml(): void
        {
            $listener = $this->createListener();
            $label = new \Contao\CoreBundle\DataContainer\RecordLabel('Wohnhaus in der Rosengasse');
            $label->htmlLabel = '<a href="/contao/preview?page=1" target="_blank"><img src="/bundles/contaocore/icons/root.svg" alt=""></a> Wohnhaus in der Rosengasse';

            $updatedLabel = $this->invokePrivateMethod($listener, 'appendStatusToLabel', [
                $label,
                ['workflow_status' => WorkflowStatus::STATUS_DRAFT],
            ]);

            $this->assertSame('Wohnhaus in der Rosengasse [Entwurf]', $updatedLabel->label);
            $this->assertSame(
                '<a href="/contao/preview?page=1" target="_blank"><img src="/bundles/contaocore/icons/root.svg" alt=""></a> Wohnhaus in der Rosengasse <span class="tl_gray">[Entwurf]</span>',
                $updatedLabel->htmlLabel
            );
        }

        public function testRendersChildRecordLabelsFromRecordLabelHtml(): void
        {
            $listener = $this->createListener();
            $label = new \Contao\CoreBundle\DataContainer\RecordLabel('Event');
            $label->htmlLabel = '<strong>Event</strong>';

            $renderedLabel = $this->invokePrivateMethod($listener, 'renderChildRecordLabel', [$label]);

            $this->assertSame('<strong>Event</strong>', $renderedLabel);
        }

        public function testNormalizesHtmlStringLabelToRecordLabel(): void
        {
            $listener = $this->createListener();

            $label = $this->invokePrivateMethod($listener, 'normalizeLabelForContao', [
                'Testinfo <span class="label-info">[18.08.2026 16:23]</span> <span class="tl_gray">[Entwurf]</span>',
            ]);

            $this->assertInstanceOf(\Contao\CoreBundle\DataContainer\RecordLabel::class, $label);
            $this->assertSame(
                'Testinfo <span class="label-info">[18.08.2026 16:23]</span> <span class="tl_gray">[Entwurf]</span>',
                $label->htmlLabel
            );
        }

        public function testBuildsConfiguredArticleLabel(): void
        {
            $listener = $this->createListener();
            $GLOBALS['TL_LANG']['COLS']['main'] = 'Hauptspalte';
            $GLOBALS['TL_DCA']['tl_article']['list']['label']['fields'] = ['title', 'inColumn', 'id'];
            $GLOBALS['TL_DCA']['tl_article']['list']['label']['format'] = '%s <span class="label-info">[%s] (ID: %s)</span>';
            $GLOBALS['TL_DCA']['tl_article']['fields']['inColumn']['reference'] = &$GLOBALS['TL_LANG']['COLS'];

            $label = $this->invokePrivateMethod($listener, 'buildConfiguredLabel', [
                ['title' => 'Home', 'inColumn' => 'main', 'id' => 117],
                'tl_article',
            ]);

            $this->assertSame('Home <span class="label-info">[Hauptspalte] (ID: 117)</span>', $label);
        }

        private function createListener(): WorkflowFieldsListener
        {
            $workflowManager = $this->createMock(WorkflowManager::class);
            $workflowManager
                ->expects($this->once())
                ->method('addEnabledTable')
                ->with('tl_newsletter');

            $container = $this->createMock(ContainerInterface::class);

            $GLOBALS['TL_LANG']['MSC']['workflow_status_ref'] = [
                WorkflowStatus::STATUS_DRAFT => 'Entwurf',
                WorkflowStatus::STATUS_REVIEW => 'In Prüfung',
                WorkflowStatus::STATUS_APPROVED => 'Freigegeben',
                WorkflowStatus::STATUS_REJECTED => 'Abgelehnt',
                WorkflowStatus::STATUS_PUBLISHED => 'Veröffentlicht',
                WorkflowStatus::STATUS_ARCHIVED => 'Archiviert',
            ];

            return new WorkflowFieldsListener($workflowManager, $container);
        }

        private function invokePrivateMethod(object $instance, string $method, array $arguments): mixed
        {
            $reflectionMethod = new ReflectionMethod($instance, $method);
            $reflectionMethod->setAccessible(true);

            return $reflectionMethod->invokeArgs($instance, $arguments);
        }
    }
}
