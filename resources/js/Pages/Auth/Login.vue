<template>
    <GuestLayout>
        <h2 class="text-2xl font-bold text-white mb-6">{{ t('Login') }}</h2>
        
        <form @submit.prevent="handleSubmit" class="space-y-6">
            <!-- Email -->
            <GlassInput
                v-model="form.email"
                type="email"
                :label="t('Email')"
                :placeholder="t('Email')"
                :icon="Mail"
                :error="form.errors.email"
                required
            />

            <!-- Password -->
            <GlassInput
                v-model="form.password"
                type="password"
                :label="t('Password')"
                placeholder="••••••••"
                :icon="Lock"
                :error="form.errors.password"
                required
            />

            <!-- Remember Me -->
            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input 
                        v-model="form.remember" 
                        type="checkbox"
                        class="w-4 h-4 rounded bg-white/10 border-white/20 text-prism-500 focus:ring-2 focus:ring-prism-500"
                    />
                    <span class="text-sm text-gray-300">{{ t('Remember me') }}</span>
                </label>

                <Link href="/forgot-password" class="text-sm text-prism-400 hover:text-prism-300">
                    {{ t('Forgot password?') }}
                </Link>
            </div>

            <!-- Submit Button -->
            <GlassButton
                type="submit"
                variant="primary"
                :loading="form.processing"
                :disabled="form.processing"
                full-width
            >
                {{ t('Login') }}
            </GlassButton>
        </form>

        <!-- Error Message -->
        <div v-if="form.errors.general" class="mt-4 p-4 glass-card border-l-4 border-red-500">
            <p class="text-sm text-red-400">{{ form.errors.general }}</p>
        </div>
    </GuestLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { Mail, Lock } from 'lucide-vue-next';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import GlassInput from '@/Components/Glass/GlassInput.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import { Link } from '@inertiajs/vue3';
import { useTranslation } from '@/Composables/useTranslation';

const { t } = useTranslation();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const handleSubmit = () => {
    form.post('/login', {
        onFinish: () => {
            form.reset('password');
        },
    });
};
</script>
