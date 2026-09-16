<?php if (isset($component)) { $__componentOriginal387b5440a2b095be4516f919dec4a4cb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal387b5440a2b095be4516f919dec4a4cb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.team','data' => ['title' => 'Escape Room - Inloggen']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.team'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Escape Room - Inloggen']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

    <div class="flex flex-1 flex-col justify-center px-6 py-12">
        <div class="mb-10 text-center">
            <div class="text-4xl">🔐</div>
            <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-white">ESCAPE ROOM</h1>
            <p class="mt-1 text-sm text-slate-400">Log in met je teamnaam en teamcode</p>
        </div>

        <form method="POST" action="<?php echo e(route('team.login.store')); ?>" class="space-y-5">
            <?php echo csrf_field(); ?>

            <div>
                <label for="team_id" class="mb-2 block text-sm font-semibold text-slate-300">Team</label>
                <select id="team_id" name="team_id" required
                        class="block w-full rounded-xl border-0 bg-slate-800 px-4 py-4 text-lg text-white focus:ring-2 focus:ring-amber-500">
                    <option value="" disabled selected>Kies je team...</option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $teams; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $team): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <option value="<?php echo e($team->id); ?>" <?php if(old('team_id') == $team->id): echo 'selected'; endif; ?>><?php echo e($team->name); ?></option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </select>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['team_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm text-red-400"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <div>
                <label for="code" class="mb-2 block text-sm font-semibold text-slate-300">Teamcode</label>
                <input id="code" type="text" name="code" inputmode="numeric" autocomplete="off" required
                       class="block w-full rounded-xl border-0 bg-slate-800 px-4 py-4 text-center text-2xl tracking-[0.5em] text-white focus:ring-2 focus:ring-amber-500"
                       placeholder="••••" maxlength="8">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm text-red-400"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <button type="submit"
                    class="w-full rounded-xl bg-amber-500 px-4 py-4 text-lg font-bold text-slate-950 shadow-lg shadow-amber-500/20 transition hover:bg-amber-400 active:scale-[0.98]">
                START
            </button>
        </form>

        <p class="mt-8 text-center text-xs text-slate-500">
            Ben je organisator? <a href="<?php echo e(route('admin.login')); ?>" class="underline">Admin login</a>
        </p>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal387b5440a2b095be4516f919dec4a4cb)): ?>
<?php $attributes = $__attributesOriginal387b5440a2b095be4516f919dec4a4cb; ?>
<?php unset($__attributesOriginal387b5440a2b095be4516f919dec4a4cb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal387b5440a2b095be4516f919dec4a4cb)): ?>
<?php $component = $__componentOriginal387b5440a2b095be4516f919dec4a4cb; ?>
<?php unset($__componentOriginal387b5440a2b095be4516f919dec4a4cb); ?>
<?php endif; ?>
<?php /**PATH /home/wilmer-mouw/Documents/code/school/escape-room-personeelsavond/resources/views/auth/team-login.blade.php ENDPATH**/ ?>