<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['game' => null]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['game' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo e($title ?? 'Escape Room Admin'); ?></title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>

</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <div class="flex min-h-screen">
        <aside class="hidden w-64 shrink-0 flex-col bg-slate-900 text-slate-100 sm:flex">
            <div class="px-6 py-5 text-lg font-bold tracking-tight">🔐 Escape Room</div>
            <nav class="flex-1 space-y-1 px-3">
                <a href="<?php echo e(route('admin.games.index')); ?>" class="block rounded-lg px-3 py-2 text-sm font-medium hover:bg-slate-800 <?php echo e(request()->routeIs('admin.games.index') ? 'bg-slate-800' : ''); ?>">Games</a>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($game)): ?>
                    <div class="mt-4 border-t border-slate-800 pt-4 text-xs font-semibold uppercase tracking-wide text-slate-500"><?php echo e($game->name); ?></div>
                    <a href="<?php echo e(route('admin.games.dashboard', $game)); ?>" class="block rounded-lg px-3 py-2 text-sm font-medium hover:bg-slate-800 <?php echo e(request()->routeIs('admin.games.dashboard') ? 'bg-slate-800' : ''); ?>">Live dashboard</a>
                    <a href="<?php echo e(route('admin.games.edit', $game)); ?>" class="block rounded-lg px-3 py-2 text-sm font-medium hover:bg-slate-800 <?php echo e(request()->routeIs('admin.games.edit') ? 'bg-slate-800' : ''); ?>">Game instellingen</a>
                    <a href="<?php echo e(route('admin.games.teams.index', $game)); ?>" class="block rounded-lg px-3 py-2 text-sm font-medium hover:bg-slate-800 <?php echo e(request()->routeIs('admin.games.teams.*') ? 'bg-slate-800' : ''); ?>">Teams</a>
                    <a href="<?php echo e(route('admin.games.rooms.index', $game)); ?>" class="block rounded-lg px-3 py-2 text-sm font-medium hover:bg-slate-800 <?php echo e(request()->routeIs('admin.games.rooms.*') ? 'bg-slate-800' : ''); ?>">Kamers</a>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <a href="<?php echo e(route('admin.audit-logs.index')); ?>" class="mt-4 block rounded-lg px-3 py-2 text-sm font-medium hover:bg-slate-800 <?php echo e(request()->routeIs('admin.audit-logs.*') ? 'bg-slate-800' : ''); ?>">Audit log</a>
            </nav>
            <form method="POST" action="<?php echo e(route('admin.logout')); ?>" class="p-3">
                <?php echo csrf_field(); ?>
                <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-300 hover:bg-slate-800">Uitloggen (<?php echo e(auth()->user()?->name); ?>)</button>
            </form>
        </aside>

        <div class="flex-1">
            <header class="flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3 sm:hidden">
                <span class="text-lg font-bold">🔐 Escape Room</span>
                <a href="<?php echo e(route('admin.games.index')); ?>" class="text-sm font-medium text-slate-600">Games</a>
            </header>

            <main class="p-4 sm:p-8">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('status')): ?>
                    <div class="mb-6 rounded-lg bg-emerald-100 px-4 py-3 text-sm font-medium text-emerald-800"><?php echo e(session('status')); ?></div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('error')): ?>
                    <div class="mb-6 rounded-lg bg-red-100 px-4 py-3 text-sm font-medium text-red-800"><?php echo e(session('error')); ?></div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php echo e($slot); ?>

            </main>
        </div>
    </div>
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>

</body>
</html>
<?php /**PATH /home/wilmer-mouw/Documents/code/school/escape-room-personeelsavond/resources/views/components/layouts/admin.blade.php ENDPATH**/ ?>