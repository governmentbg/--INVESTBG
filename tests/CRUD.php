<?php

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;

abstract class CRUD extends TestCase
{
    use HTTPTrait;

    protected $slug;
    protected $create = true;
    protected $read   = false;
    protected $update = true;
    protected $delete = true;

    protected $fields = [];
    protected $search = null;

    protected $id;
    protected $rand;
    protected $logged = false;

    public function setUp(): void
    {
        if (!$this->logged) {
            $this
                ->clear()
                ->get('/')
                ->follow()
                ->post(['username' => 'mockauth', 'password' => 'mockauth'])
                ->follow()
                    ->assertStatus(200)
                    ->assertLocation('/')
                    ->assertBodyContains('Администратор');
            $this->logged = true;
        }
        if (!$this->search) {
            $this->search = array_values($this->fields)[0];
        }
        if (!$this->rand) {
            do {
                $rand = sha1(random_bytes(16));
                $this->get($this->slug . '?' . $this->search . '=' . $rand);
                if (mb_strpos((string)$this->response->getBody(), 'Няма записи')) {
                    $this->rand = $rand;
                    break;
                }
            } while (true);
        }
    }

    protected function getCreateData(): array
    {
        $data = [];
        foreach ($this->fields as $field) {
            $data[$field] = $this->search === $field ? $this->rand : $this->rand . '_' . $field;
        }
        return $data;
    }
    protected function getUpdateData(): array
    {
        $data = $this->getCreateData();
        $data[$this->search] .= '_upd';
        return $data;
    }

    public function testCRUDCycle(): void
    {
        if (!$this->create) {
            $this->fail('Cannot create record');
        }
        $this->get($this->slug . '/create')
            ->assertStatus(200);
        $data = $this->getCreateData();
        $this->rand = $data[$this->search];
        $this
            ->post($this->slug . '/create', $data)
            ->assertStatus(303)
            ->follow()
            ->assertStatus(200)
            ->get($this->slug . '?' . $this->search . '=' . $data[$this->search])
            ->assertBodyContains('Записи от 1 до 1 от общо 1');
        $temp = explode($this->slug . '/update/', (string)$this->response->getBody());
        if ($temp && isset($temp[1])) {
            $this->id = explode('"', $temp[1])[0] ?? null;
        }
        if (!$this->id) {
            $this->fail('Could not extract created ID');
        }
        if ($this->read) {
            $this
                ->get($this->slug . '/read/' . $this->id)
                ->assertStatus(200);
        }
        if ($this->update) {
            $this
                ->get($this->slug . '/update/' . $this->id)
                ->assertStatus(200);
            $data = $this->getUpdateData();
            $this->rand = $data[$this->search];
            $this
                ->post($this->slug . '/update/' . $this->id, $data)
                ->assertStatus(303)
                ->follow()
                ->assertStatus(200)
                ->get($this->slug . '?' . $this->search . '=' . $data[$this->search])
                ->assertBodyContains('Записи от 1 до 1 от общо 1');
        }
        if ($this->delete) {
            $this
                ->get($this->slug . '/delete/' . $this->id)
                ->assertStatus(200);
            $this
                ->post($this->slug . '/delete/' . $this->id, [])
                ->assertStatus(303)
                ->follow()
                ->assertStatus(200);
        }
    }
}
