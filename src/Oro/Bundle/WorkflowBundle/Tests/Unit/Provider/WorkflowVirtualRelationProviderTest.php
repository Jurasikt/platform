<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Bundle\WorkflowBundle\Provider\WorkflowVirtualRelationProvider;

class WorkflowVirtualRelationProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var WorkflowRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowRegistry;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var WorkflowVirtualRelationProvider */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->workflowRegistry = $this->getMockBuilder(WorkflowRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new WorkflowVirtualRelationProvider($this->workflowRegistry, $this->doctrineHelper);
    }

    // testIsVirtualRelation
    public function testIsVirtualRelationAndUnknownRelationFieldName()
    {
        $this->doctrineHelper->expects($this->never())->method('getSingleEntityIdentifierFieldName');
        $this->workflowRegistry->expects($this->never())->method('getActiveWorkflowsByEntityClass');

        $this->assertFalse($this->provider->isVirtualRelation('stdClass', 'unknown_relation'));
    }

    public function testIsVirtualRelationAndNoApplicableWorkflows()
    {
        $this->doctrineHelper->expects($this->never())->method('getSingleEntityIdentifierFieldName');

        $this->assertWorkflowManagerCalled(false);

        $this->assertFalse(
            $this->provider->isVirtualRelation('stdClass', WorkflowVirtualRelationProvider::ITEMS_RELATION_NAME)
        );
    }

    public function testIsVirtualRelationAndItemsRelation()
    {
        $this->doctrineHelper->expects($this->never())->method('getSingleEntityIdentifierFieldName');

        $this->assertWorkflowManagerCalled(true, 'stdClass');

        $this->assertTrue(
            $this->provider->isVirtualRelation('stdClass', WorkflowVirtualRelationProvider::ITEMS_RELATION_NAME)
        );
    }

    public function testIsVirtualRelationAndStepsRelation()
    {
        $this->doctrineHelper->expects($this->never())->method('getSingleEntityIdentifierFieldName');

        $this->assertWorkflowManagerCalled(true, 'stdClass');

        $this->assertTrue(
            $this->provider->isVirtualRelation('stdClass', WorkflowVirtualRelationProvider::STEPS_RELATION_NAME)
        );
    }

    public function testGetVirtualRelationsAndNoApplicableWorkflows()
    {
        $this->doctrineHelper->expects($this->never())->method('getSingleEntityIdentifierFieldName');

        $this->assertWorkflowManagerCalled(false);

        $this->assertEquals([], $this->provider->getVirtualRelations('stdClass'));
    }

    public function testGetVirtualRelations()
    {
        $this->doctrineHelper->expects($this->never())->method('getSingleEntityIdentifierFieldName');

        $this->assertWorkflowManagerCalled(true, 'stdClass');

        $this->assertEquals(
            [
                WorkflowVirtualRelationProvider::ITEMS_RELATION_NAME => [
                    'label' => 'oro.workflow.workflowitem.entity_label',
                    'relation_type' => 'OneToMany',
                    'related_entity_name' => 'Oro\Bundle\WorkflowBundle\Entity\WorkflowItem',
                ],
                WorkflowVirtualRelationProvider::STEPS_RELATION_NAME => [
                    'label' => 'oro.workflow.workflowstep.entity_label',
                    'relation_type' => 'OneToMany',
                    'related_entity_name' => 'Oro\Bundle\WorkflowBundle\Entity\WorkflowStep',
                ],
            ],
            $this->provider->getVirtualRelations('stdClass')
        );
    }

    // testGetVirtualRelationsQuery
    public function testGetVirtualRelationQueryAndNoApplicableWorkflows()
    {
        $this->doctrineHelper->expects($this->never())->method('getSingleEntityIdentifierFieldName');

        $this->assertWorkflowManagerCalled(false);

        $this->assertEquals(
            [],
            $this->provider->getVirtualRelationQuery('stdClass', WorkflowVirtualRelationProvider::ITEMS_RELATION_NAME)
        );
    }

    public function testGetVirtualRelationQueryAndUnknownRelationFieldName()
    {
        $this->doctrineHelper->expects($this->never())->method('getSingleEntityIdentifierFieldName');
        $this->workflowRegistry->expects($this->never())->method('getActiveWorkflowsByEntityClass');

        $this->assertEquals([], $this->provider->getVirtualRelationQuery('stdClass', 'unknown_field'));
    }

    public function testGetVirtualRelationQuery()
    {
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifierFieldName')
            ->with('stdClass')
            ->willReturn('id');

        $this->assertWorkflowManagerCalled(true);

        $this->assertEquals(
            [
                'join' => [
                    'left' => [
                        [
                            'join' => WorkflowItem::class,
                            'alias' => WorkflowVirtualRelationProvider::ITEMS_RELATION_NAME,
                            'conditionType' => Join::WITH,
                            'condition' => sprintf(
                                'CAST(entity.%s as string) = CAST(%s.entityId as string) AND %s.entityClass = \'%s\'',
                                'id',
                                WorkflowVirtualRelationProvider::ITEMS_RELATION_NAME,
                                WorkflowVirtualRelationProvider::ITEMS_RELATION_NAME,
                                'stdClass'
                            )
                        ],
                        [
                            'join' => sprintf('%s.currentStep', WorkflowVirtualRelationProvider::ITEMS_RELATION_NAME),
                            'alias' => WorkflowVirtualRelationProvider::STEPS_RELATION_NAME,
                        ]
                    ]
                ]
            ],
            $this->provider->getVirtualRelationQuery('stdClass', WorkflowVirtualRelationProvider::ITEMS_RELATION_NAME)
        );
    }

    // testGetTargetJoinAlias
    public function testGetTargetJoinAlias()
    {
        $this->assertEquals('virtual_relation', $this->provider->getTargetJoinAlias('', 'virtual_relation'));
    }

    /**
     * @param bool $result
     * @param string $class
     */
    protected function assertWorkflowManagerCalled($result, $class = null)
    {
        $mocker = $this->workflowRegistry->expects($this->once())
            ->method('hasActiveWorkflowsByEntityClass')
            ->willReturn($result);

        if ($class) {
            $mocker->with($class);
        }
    }
}
