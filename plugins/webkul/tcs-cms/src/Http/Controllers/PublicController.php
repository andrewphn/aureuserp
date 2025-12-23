<?php

namespace Webkul\TcsCms\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\TcsCms\Models\HomeSection;
use Webkul\TcsCms\Models\Journal;
use Webkul\TcsCms\Models\PortfolioProject;

class PublicController extends Controller
{
    /**
     * Display the homepage
     */
    public function home()
    {
        $homeSections = HomeSection::where('is_active', true)
            ->orderBy('position')
            ->get();

        return view('tcs-cms::public.home', [
            'homeSections' => $homeSections,
        ]);
    }

    /**
     * Display work/portfolio page
     */
    public function work()
    {
        $projects = PortfolioProject::where('is_published', true)
            ->orderBy('portfolio_order')
            ->get();

        return view('tcs-cms::public.work', [
            'projects' => $projects,
        ]);
    }

    /**
     * Display single work/portfolio item
     */
    public function workShow($slug)
    {
        $project = PortfolioProject::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return view('tcs-cms::public.work-show', [
            'project' => $project,
        ]);
    }

    /**
     * Display journal page
     */
    public function journal()
    {
        $journals = Journal::where('is_published', true)
            ->orderBy('published_at', 'desc')
            ->get();

        return view('tcs-cms::public.journal', [
            'journals' => $journals,
        ]);
    }

    /**
     * Display single journal item
     */
    public function journalShow($slug)
    {
        $journal = Journal::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return view('tcs-cms::public.journal-show', [
            'journal' => $journal,
        ]);
    }
}
