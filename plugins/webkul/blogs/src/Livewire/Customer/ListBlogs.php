<?php

namespace Webkul\Blog\Livewire\Customer;

use Livewire\Component;
use Webkul\Blog\Models\Blog;

class ListBlogs extends Component
{
    public function render()
    {
        return view('blogs::livewire.customer.list-blogs', [
            'blogs' => Blog::query()->paginate(9),
        ]);
    }
}
