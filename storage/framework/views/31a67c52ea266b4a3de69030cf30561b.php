<?php if (isset($component)) { $__componentOriginalc8c9fd5d7827a77a31381de67195f0c3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc8c9fd5d7827a77a31381de67195f0c3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.admin','data' => ['game' => $game,'title' => 'Team bewerken']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.admin'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['game' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($game),'title' => 'Team bewerken']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

    <h1 class="mb-6 text-2xl font-bold text-slate-900">Team bewerken</h1>

    <form method="POST" action="<?php echo e(route('admin.games.teams.update', [$game, $team])); ?>" class="max-w-lg space-y-4 rounded-xl bg-white p-6 shadow-sm">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PUT'); ?>

        <div>
            <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Teamnaam</label>
            <input id="name" name="name" type="text" value="<?php echo e(old('name', $team->name)); ?>" required
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

        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="hidden" name="active" value="0">
            <input type="checkbox" name="active" value="1" <?php if(old('active', $team->active)): echo 'checked'; endif; ?> class="rounded border-slate-300">
            Actief (team mag inloggen en spelen)
        </label>

        <button type="submit" class="w-full rounded-lg bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-800">
            Opslaan
        </button>
    </form>
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
<?php /**PATH /home/wilmer-mouw/Documents/code/school/escape-room-personeelsavond/resources/views/admin/teams/edit.blade.php ENDPATH**/ ?>