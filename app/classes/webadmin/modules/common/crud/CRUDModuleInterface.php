<?php

declare(strict_types=1);

namespace webadmin\modules\common\crud;

use webadmin\components\html\Form;
use webadmin\components\html\Table;
use webadmin\modules\VisualModuleInterface;

/**
 * @template T of \vakata\database\schema\Entity
 * @template-covariant S of CRUDServiceInterface<T>
 */
interface CRUDModuleInterface extends VisualModuleInterface
{
    public function getTable(): string;
    public function getViews(): ?string;

    /**
     * @param Table $table
     * @return Table
     */
    public function listingCallback(Table $table): Table;
    /**
     * @param Form $form
     * @return Form
     */
    public function formCallback(Form $form): Form;
    /**
     * @return S
     */
    public function getService(): CRUDServiceInterface;
    /**
     * @return CRUDFormsInterface<T>
     */
    public function getForms(): CRUDFormsInterface;

    public function canCreate(): bool;
    public function canRead(): bool;
    public function canUpdate(): bool;
    public function canDelete(): bool;
    public function canCopy(): bool;
    public function hasHistory(): bool;
}
