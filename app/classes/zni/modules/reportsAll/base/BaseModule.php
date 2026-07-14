<?php

declare(strict_types=1);

namespace zni\modules\reportsAll\base;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use vakata\collection\Collection;
use vakata\http\Request;
use vakata\http\Response;
use vakata\validation\Validator;
use vakata\views\Views;
use webadmin\components\html\Form;
use webadmin\components\html\Table;
use webadmin\components\html\TableColumn;
use webadmin\components\html\TableRow;
use webadmin\modules\VisualModuleInterface;
use vakata\config\Config;

/**
 * @template T of BaseService
 */
abstract class BaseModule implements VisualModuleInterface
{
    /**
     * @param T $service
     * @param string $name
     * @param string $slug
     * @param string $icon
     * @param string $color
     * @param string $parent
     * @return void
     */
    public function __construct(
        protected BaseService $service,
        protected Views $views,
        protected Config $config,
        protected string $name,
        protected string $slug,
        protected string $icon,
        protected string $color,
        protected string $parent
    ) {
        $this->views->addFolder(self::getName(), __DIR__ . '/views');
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function getSlug(): string
    {
        return $this->slug;
    }
    public function getIcon(): string
    {
        return $this->icon;
    }
    public function getColor(): string
    {
        return $this->color;
    }
    public function getParent(): string
    {
        return $this->parent;
    }
    public function onDashboard(): bool
    {
        return true;
    }
    public function inMenu(): bool
    {
        return true;
    }
    protected function getViews(): string
    {
        return self::getName();
    }
    public function process(Request $request): Response
    {
        if ($request->getMethod() === 'POST') {
             return (new Response())->setBody(
                 $this->views->render(
                     'webadmin::form',
                     [
                        'form' => $this->getForm($request->getPost())
                     ]
                 )
             );
        }

        if ($request->getUrl()->getSegment(1) === 'export') {
            return $this->export($request);
        }

        $params = $request->getQuery();
        $form = $this->getForm($params);

        if (isset($params['submit']) && (int) $params['submit']) {
            $errors = [];
            foreach ($form->getValidator()->run($params) as $k => $v) {
                $errors[] = $this->getName() . '.' . $v['key'] . '.' . $v['rule'];
            }
            if (count($errors)) {
                $session = $request->getAttribute('session');
                $session->set('error', $errors);

                $table = $this->getTable([]);
            } else {
                $table = $this->getTable($this->service->select($params));
            }
        } else {
            $table = $this->getTable([]);
        }

        return new Response(
            200,
            $this->views
                ->render(
                    $this->getViews() . '::index',
                    [
                        'module'    => $this,
                        'form'      => $form,
                        'table'     => $table
                    ]
                )
        );
    }
    protected function getForm(array $data = []): Form
    {
        $form = new Form();

        return $form
            ->setValidator(new Validator())
            ->populate($data);
    }
    protected function getTable(array $data): Table
    {
        $table = (new Table())
            ->setAttr('x-data-paging', false)
            ->setAttr('x-data-params', [])
            ->setAttr('x-data-filters', [])
            ->setAttr('x-data-count', count($data))
            ->setAttr('x-data-search', false)
            ->addClass('ui basic selectable compact main-table single line table overflowing head last stuck');

        foreach ($this->service->columns() as $column) {
            $table->addColumn(new TableColumn($column));
        }

        $table->setOrder($this->service->columns());

        foreach ($data as $id => $values) {
            $table->addRow(
                (new TableRow($values))
                    ->setAttr('id', $id)
                    ->setData((object) $values)
            );
        }

        return $table;
    }
    protected function export(Request $request): Response
    {
        $params = $request->getQuery();
        $form = $this->getForm($params);

        if (!isset($params['submit']) || !(int) $params['submit']) {
            return (new Response(303))
                ->withHeader('Location', $request->getUrl()->get($this->getSlug()));
        }
        $errors = [];
        foreach ($form->getValidator()->run($params) as $k => $v) {
            $errors[] = $this->getName() . '.' . $v['key'] . '.' . $v['rule'];
        }
        if (count($errors)) {
            $session = $request->getAttribute('session');
            $session->set('error', $errors);

            return (new Response(303))
                ->withHeader('Location', $request->getUrl()->get($this->getSlug()));
        }

        $table = $this->getTable($this->service->select($params));
        return $this->generateExcel($table);
    }

    private function generateExcel(Table $table): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        /**
         * @psalm-suppress UndefinedThisPropertyFetch
         * @phpstan-ignore property.notFound
         */
        $intl = $this->intl;

        $columns = $this->service->columns();

        foreach ($columns as $index => $col) {
            $columnIndex = (int)$index + 1;
            $columnLetter = Coordinate::stringFromColumnIndex($columnIndex);

            $sheet->setCellValue($columnLetter . '1', $intl->get('table.columns.' . $col));
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);

            if ($col === 'number_persons') {
                $sheet->getStyle($columnLetter)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                //$sheet->getStyle($columnLetter . '1')->getFont()->setBold(true);
            }
        }

        $data = Collection::from($table->getRows())
            ->map(function (TableRow $row) use ($table) {
                $values = [];
                foreach ($table->getColumns() as $column) {
                    $temp = explode('.', $column->getName());
                    $value = $row->getData();
                    foreach ($temp as $part) {
                        if (
                            $value === null ||
                            !is_object($value) ||
                            (!property_exists($value, $part) && !method_exists($value, '__get'))
                        ) {
                            $value = '';
                            break;
                        }
                        $value = $value->{$part};
                    }
                    if ($column->hasMap() && $column->getMap()) {
                        $map = $column->getMap();
                        $value = is_callable($map)
                            ? call_user_func($map, $value, $row->getData())
                            : $value;
                    }

                    $values[] = $value;
                }

                return $values;
            })->toArray();

        $sheet->fromArray($data, null, 'A2');

        $writer = new Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="report.xlsx"');
        $writer->save('php://output');
        exit;
    }
}
