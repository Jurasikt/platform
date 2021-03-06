<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Model\Filter;

use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\TestActivityScopeProvider;

use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadTestActivitiesForScopes;
use Oro\Bundle\WorkflowBundle\Tests\Functional\WorkflowTestCase;

/**
 * @dbIsolation
 */
class WorkflowDefinitionScopesRegistryFilterTest extends WorkflowTestCase
{
    const WORKFLOW_SCOPES_CONFIG_DIR = '/Tests/Functional/DataFixtures/WithScopesAndWithout';

    /**
     * @var TestActivityScopeProvider
     */
    private $activityScopeProvider;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadTestActivitiesForScopes::class]);
        $this->activityScopeProvider = new TestActivityScopeProvider();
        self::getContainer()->get('oro_scope.scope_manager')
            ->addProvider('workflow_definition', $this->activityScopeProvider);
    }

    public function testFilter()
    {
        /** @var TestActivity $initialActivity */
        $initialActivity = $this->getReference('test_activity_1');

        self::getContainer()->get('oro_workflow.changes.event.dispatcher')->addListener(
            WorkflowEvents::WORKFLOW_BEFORE_CREATE,
            function (WorkflowChangesEvent $changesEvent) use ($initialActivity) {
                $definition = $changesEvent->getDefinition();
                if ($definition->getName() === 'test_flow_with_scopes') {
                    $definition->setScopesConfig(
                        [
                            [
                                'test_activity' => $initialActivity->getId()
                            ]
                        ]
                    );
                }
            }
        );

        self::loadWorkflowFrom(self::WORKFLOW_SCOPES_CONFIG_DIR);

        $registry = self::getContainer()->get('oro_workflow.registry');

        $this->activityScopeProvider->setCurrentTestActivity($initialActivity);

        $workflows = $registry->getActiveWorkflowsByEntityClass(WorkflowAwareEntity::class);

        $expectedWorkflows = ['test_flow_with_scopes', 'test_flow_without_scopes'];

        $this->assertTrue($workflows->forAll(function ($name, Workflow $workflow) use ($expectedWorkflows) {
            return in_array($workflow->getName(), $expectedWorkflows, true);
        }));

        //changing context
        $this->activityScopeProvider->setCurrentTestActivity($this->getReference('test_activity_2'));

        $workflows = $registry->getActiveWorkflowsByEntityClass(WorkflowAwareEntity::class);

        $expectedWorkflows = ['test_flow_without_scopes'];

        $this->assertTrue($workflows->forAll(function ($name, Workflow $workflow) use ($expectedWorkflows) {
            return in_array($workflow->getName(), $expectedWorkflows, true);
        }));

        $this->assertFalse($workflows->exists(function ($name, Workflow $workflow) {
            return $workflow->getName() === 'test_flow_with_scopes';
        }));
    }
}
