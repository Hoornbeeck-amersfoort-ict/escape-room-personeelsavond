<?php if (isset($component)) { $__componentOriginalc8c9fd5d7827a77a31381de67195f0c3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.admin','data' => ['game' => $game,'title' => 'Teams']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.admin'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['game' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($game),'title' => 'Teams']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-900">Teams — <?php echo e($game->name); ?></h1>
        <a href="<?php echo e(route('admin.games.teams.create', $game)); ?>" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">+ Team toevoegen</a>
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Naam</th>
                    <th class="px-4 py-3">Code</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Sessies</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $teams; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $team): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <tr>
                        <td class="px-4 py-3 font-semibold text-slate-900"><?php echo e($team->name); ?></td>
                        <td class="px-4 py-3 font-mono text-slate-700"><?php echo e($team->code ?? '—'); ?></td>
                        <td class="px-4 py-3">
                            <span class="<?php echo e($team->active ? 'text-emerald-600' : 'text-red-600'); ?> font-semibold">
                                <?php echo e($team->active ? 'Actief' : 'Geblokkeerd'); ?>

                            </span>
                        </td>
                        <td class="px-4 py-3"><?php echo e($team->room_sessions_count); ?></td>
                        <td class="px-4 py-3 text-right text-sm">
                            <a href="<?php echo e(route('admin.games.teams.edit', [$game, $team])); ?>" class="font-semibold text-amber-700 underline">Bewerken</a>

                            <form method="POST" action="<?php echo e(route('admin.games.teams.reset-code', [$game, $team])); ?>" class="inline">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="ml-3 font-semibold text-slate-700 underline">Code resetten</button>
                            </form>

                            <form method="POST" action="<?php echo e(route('admin.games.teams.toggle-active', [$game, $team])); ?>" class="inline">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="ml-3 font-semibold text-slate-700 underline">
                                    <?php echo e($team->active ? 'Blokkeren' : 'Activeren'); ?>

                                </button>
                            </form>

                            <form method="POST" action="<?php echo e(route('admin.games.teams.destroy', [$game, $team])); ?>" class="inline" onsubmit="return confirm('Team en alle bijbehorende sessies verwijderen?');">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="ml-3 font-semibold text-red-700 underline">Verwijderen</button>
                            </form>
                        </td>
                    </tr>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-slate-500">Nog geen teams.</td>
                    </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </tbody>
        </table>
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
<?php /**PATH /home/wilmer-mouw/Documents/code/school/escape-room-personeelsavond/resources/views/admin/teams/index.blade.php ENDPATH**/ ?>