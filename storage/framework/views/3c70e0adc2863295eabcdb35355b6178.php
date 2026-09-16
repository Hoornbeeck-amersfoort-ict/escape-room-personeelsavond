<?php if (isset($component)) { $__componentOriginalc8c9fd5d7827a77a31381de67195f0c3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.admin','data' => ['game' => $game,'title' => ''.e($game->name).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.admin'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['game' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($game),'title' => ''.e($game->name).'']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

    <h1 class="mb-1 text-2xl font-bold text-slate-900"><?php echo e($game->name); ?></h1>
    <p class="mb-6 text-sm text-slate-500">Status: <span class="font-semibold"><?php echo e($game->status->label()); ?></span></p>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl bg-white p-6 shadow-sm">
            <h2 class="mb-4 font-bold text-slate-900">Instellingen</h2>
            <form method="POST" action="<?php echo e(route('admin.games.update', $game)); ?>" class="space-y-4">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>

                <div>
                    <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Naam</label>
                    <input id="name" name="name" type="text" value="<?php echo e(old('name', $game->name)); ?>" required
                           class="block w-full rounded-lg border-slate-300 focus:border-slate-900 focus:ring-slate-900">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <div>
                    <label for="start_time" class="mb-1 block text-sm font-medium text-slate-700">Starttijd</label>
                    <input id="start_time" name="start_time" type="datetime-local"
                           value="<?php echo e(old('start_time', $game->start_time?->format('Y-m-d\TH:i'))); ?>"
                           class="block w-full rounded-lg border-slate-300 focus:border-slate-900 focus:ring-slate-900">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['start_time'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <div>
                    <label for="end_time" class="mb-1 block text-sm font-medium text-slate-700">Eindtijd</label>
                    <input id="end_time" name="end_time" type="datetime-local"
                           value="<?php echo e(old('end_time', $game->end_time?->format('Y-m-d\TH:i'))); ?>"
                           class="block w-full rounded-lg border-slate-300 focus:border-slate-900 focus:ring-slate-900">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['end_time'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <button type="submit" class="w-full rounded-lg bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-800">
                    Opslaan
                </button>
            </form>
        </div>

        <div class="space-y-6">
            <div class="rounded-xl bg-white p-6 shadow-sm">
                <h2 class="mb-4 font-bold text-slate-900">Overzicht</h2>
                <ul class="space-y-2 text-sm">
                    <li><a href="<?php echo e(route('admin.games.teams.index', $game)); ?>" class="font-semibold text-amber-700 underline"><?php echo e($game->teams()->count()); ?> teams beheren</a></li>
                    <li><a href="<?php echo e(route('admin.games.rooms.index', $game)); ?>" class="font-semibold text-amber-700 underline"><?php echo e($game->rooms()->count()); ?> kamers beheren</a></li>
                    <li><a href="<?php echo e(route('admin.games.dashboard', $game)); ?>" class="font-semibold text-amber-700 underline">Live dashboard bekijken</a></li>
                </ul>
            </div>

            <div class="rounded-xl bg-white p-6 shadow-sm">
                <h2 class="mb-4 font-bold text-slate-900">Spelbeheer</h2>
                <div class="space-y-2">
                    <form method="POST" action="<?php echo e(route('admin.games.start', $game)); ?>" onsubmit="return confirm('Game starten?');">
                        <?php echo csrf_field(); ?>
                        <button type="submit" <?php if($game->status->value !== 'draft' || $game->end_time === null): echo 'disabled'; endif; ?>
                                class="w-full rounded-lg bg-emerald-600 px-4 py-3 font-semibold text-white disabled:opacity-40">
                            Game starten
                        </button>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($game->status->value === 'draft' && $game->end_time === null): ?>
                            <p class="mt-1 text-xs text-slate-500">Stel eerst een eindtijd in hierboven.</p>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </form>
                    <form method="POST" action="<?php echo e(route('admin.games.finish', $game)); ?>" onsubmit="return confirm('Weet je zeker dat je het game nu wilt beëindigen?');">
                        <?php echo csrf_field(); ?>
                        <button type="submit" <?php if($game->status->value !== 'running'): echo 'disabled'; endif; ?>
                                class="w-full rounded-lg bg-red-600 px-4 py-3 font-semibold text-white disabled:opacity-40">
                            Game beëindigen
                        </button>
                    </form>
                    <form method="POST" action="<?php echo e(route('admin.games.reset', $game)); ?>" onsubmit="return confirm('Alle voortgang (sessies, punten, pogingen) wordt gewist. Doorgaan?');">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="w-full rounded-lg bg-slate-200 px-4 py-3 font-semibold text-slate-800">
                            Game resetten
                        </button>
                    </form>
                    <form method="POST" action="<?php echo e(route('admin.games.destroy', $game)); ?>" onsubmit="return confirm('Dit game inclusief teams en kamers permanent verwijderen?');">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="w-full rounded-lg px-4 py-3 font-semibold text-red-700 underline">
                            Game verwijderen
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3)): ?>
<?php $attributes = $__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3; ?>
<?php unset($__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc8c9fd5d7827a77a31381de67195f0c3)): ?>
<?php $component = $__componentOriginalc8c9fd5d7827a77a31381de67195f0c3; ?>
<?php unset($__componentOriginalc8c9fd5d7827a77a31381de67195f0c3); ?>
<?php endif; ?>
<?php /**PATH /home/wilmer-mouw/Documents/code/school/escape-room-personeelsavond/resources/views/admin/games/edit.blade.php ENDPATH**/ ?>