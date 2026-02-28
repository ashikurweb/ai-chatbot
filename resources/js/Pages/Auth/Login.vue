<template>
  <div class="min-h-screen bg-[#fcfcfc] dark:bg-[#171717] flex flex-col items-center justify-center p-4 transition-colors duration-500">
    <!-- Main Shell -->
    <div class="w-full max-w-[400px] space-y-8 animate-fade-in">
      
      <!-- Logo/Brand Section -->
      <div class="text-center space-y-2">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-indigo-600 text-white shadow-xl shadow-indigo-500/20 mb-4 transition-transform hover:scale-110 active:scale-95 cursor-default">
           <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
           </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">Welcome back</h1>
        <p class="text-gray-500 dark:text-gray-400 text-[15px]">Sign in to continue your chat</p>
      </div>

      <!-- Social Login Section -->
      <div class="space-y-3">
        <!-- Google -->
        <a 
          href="/auth/google/redirect"
          class="flex w-full items-center justify-center gap-3 bg-white dark:bg-[#212121] border border-gray-200 dark:border-[#2f2f2f] hover:bg-gray-50 dark:hover:bg-[#2f2f2f] text-gray-700 dark:text-gray-200 font-medium py-3 rounded-xl transition-all shadow-sm group active:scale-[0.98]"
        >
          <img src="https://www.gstatic.com/images/branding/product/1x/googleg_48dp.png" alt="Google" class="w-5 h-5 grayscale group-hover:grayscale-0 transition-all" />
          <span>Continue with Google</span>
        </a>

        <!-- GitHub -->
        <a 
          href="/auth/github/redirect"
          class="flex w-full items-center justify-center gap-3 bg-white dark:bg-[#212121] border border-gray-200 dark:border-[#2f2f2f] hover:bg-gray-50 dark:hover:bg-[#2f2f2f] text-gray-700 dark:text-gray-200 font-medium py-3 rounded-xl transition-all shadow-sm group active:scale-[0.98]"
        >
          <svg class="w-5 h-5 fill-current grayscale group-hover:grayscale-0 transition-all text-gray-900 dark:text-white" viewBox="0 0 24 24">
            <path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/>
          </svg>
          <span>Continue with GitHub</span>
        </a>

        <!-- Facebook -->
        <a 
          href="/auth/facebook/redirect"
          class="flex w-full items-center justify-center gap-3 bg-white dark:bg-[#212121] border border-gray-200 dark:border-[#2f2f2f] hover:bg-gray-50 dark:hover:bg-[#2f2f2f] text-gray-700 dark:text-gray-200 font-medium py-3 rounded-xl transition-all shadow-sm group active:scale-[0.98]"
        >
           <svg class="w-5 h-5 fill-[#1877F2] grayscale group-hover:grayscale-0 transition-all" viewBox="0 0 24 24">
             <path d="M9.101 23.691v-7.98H6.627v-3.667h2.474v-1.58c0-4.03 2.302-5.965 5.486-5.965 1.524 0 3.111.272 3.111.272v3.42h-1.752c-2.016 0-2.643 1.242-2.643 2.522v1.332h3.854l-.616 3.667h-3.238v7.98h-3.754z"/>
           </svg>
          <span>Continue with Facebook</span>
        </a>
      </div>

      <!-- Separator -->
      <div class="relative py-4">
        <div class="absolute inset-0 flex items-center">
          <div class="w-full border-t border-gray-200 dark:border-[#2f2f2f]"></div>
        </div>
        <div class="relative flex justify-center text-xs uppercase">
          <span class="bg-[#fcfcfc] dark:bg-[#171717] px-4 text-gray-500 font-semibold tracking-widest">OR</span>
        </div>
      </div>

      <!-- Guest Access -->
      <div class="text-center">
        <a 
          href="/" 
          class="text-[14px] font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors"
        >
          Try as a guest
        </a>
      </div>

      <!-- Footer Policy -->
      <div class="text-center pt-8">
         <p class="text-[12px] text-gray-400 dark:text-gray-500 leading-relaxed px-8">
           By continuing, you agree to our 
           <a href="#" class="underline hover:text-gray-600 dark:hover:text-gray-400">Terms of Service</a> 
           and 
           <a href="#" class="underline hover:text-gray-600 dark:hover:text-gray-400">Privacy Policy</a>.
         </p>
      </div>

    </div>
  </div>
</template>

<script setup>
// No extra logic needed for now
</script>

<style scoped>
@keyframes fade-in {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
  animation: fade-in 0.6s cubic-bezier(0.22, 1, 0.36, 1);
}
</style>
