<template>
  <Head :title="activeConversationTitle || 'AI Chatbot'" />

  <div :class="['flex h-screen overflow-hidden transition-colors duration-500', isDark ? 'bg-[#212121] text-white' : 'bg-white text-gray-800']">

    <!-- Toast Notification -->
    <Transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="translate-y-[-20px] opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="translate-y-0 opacity-100"
      leave-to-class="translate-y-[-20px] opacity-0"
    >
      <div v-if="toast.show"
        :class="[
          'fixed top-6 left-1/2 -translate-x-1/2 z-100 px-5 py-3 rounded-2xl shadow-2xl text-sm font-medium flex items-center gap-3 min-w-[300px]',
          toast.type === 'success'
            ? (isDark ? 'bg-emerald-900 border border-emerald-700 text-emerald-100' : 'bg-emerald-50 border border-emerald-200 text-emerald-800')
            : (isDark ? 'bg-red-900 border border-red-700 text-red-100' : 'bg-red-50 border border-red-200 text-red-800'),
        ]">
        {{ toast.message }}
      </div>
    </Transition>

    <!-- Sidebar -->
    <aside
      :class="[
        'fixed inset-y-0 left-0 z-50 flex w-72 flex-col transition-transform duration-300 md:relative md:translate-x-0',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full',
        isDark ? 'bg-[#171717] border-r border-[#2f2f2f]' : 'bg-[#f9f9f9] border-r border-gray-200'
      ]"
    >
      <!-- New Chat Button -->
      <div class="p-3">
        <button
          @click="startNewChat"
          :class="[
            'flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
            isDark ? 'bg-[#212121] hover:bg-[#2f2f2f] text-white' : 'bg-white hover:bg-gray-100 text-gray-800 border border-gray-200 shadow-sm'
          ]"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
          </svg>
          New Chat
        </button>
      </div>

      <!-- Conversations List -->
      <nav class="flex-1 overflow-y-auto px-3 custom-scrollbar">
        <div class="mb-2 px-3 py-2 text-[10px] font-bold uppercase tracking-wider text-gray-500">
          History
        </div>
        <div class="space-y-1">
          <div
            v-for="conv in conversations"
            :key="conv.id"
            class="group relative"
          >
            <button
              @click="switchConversation(conv.id)"
              :class="[
                'flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm transition-all',
                activeConversationId === conv.id
                  ? (isDark ? 'bg-[#2f2f2f] text-white' : 'bg-gray-200 text-gray-900 shadow-sm')
                  : (isDark ? 'hover:bg-[#212121] text-gray-400' : 'hover:bg-gray-100 text-gray-600')
              ]"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 transition-colors" :class="activeConversationId === conv.id ? 'text-indigo-400' : 'text-gray-500 group-hover:text-indigo-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
              </svg>
              <span class="truncate pr-6">{{ conv.title }}</span>
            </button>
            <!-- Delete Button -->
            <button
              @click.stop="deleteConversation(conv.id)"
              class="absolute right-2 top-1/2 -translate-y-1/2 rounded-md p-1 opacity-0 group-hover:opacity-100 hover:bg-red-500/10 text-gray-500 hover:text-red-500 transition-all"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>
      </nav>

      <!-- Bottom User Section -->
      <div :class="['p-3 border-t space-y-2', isDark ? 'border-[#2f2f2f]' : 'border-gray-200']">
        <!-- User Profile -->
        <div v-if="auth.user" class="relative">
          <!-- Transparent Overlay for closing dropdown -->
          <div v-if="showUserMenu" @click="showUserMenu = false" class="fixed inset-0 z-40"></div>
          
          <!-- Dropdown Menu -->
          <Transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0 translate-y-2"
            enter-to-class="transform scale-100 opacity-100 translate-y-0"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100 translate-y-0"
            leave-to-class="transform scale-95 opacity-0 translate-y-2"
          >
            <div
              v-if="showUserMenu"
              :class="[
                'absolute bottom-full left-0 mb-2 w-[240px] rounded-2xl shadow-xl ring-1 z-50 overflow-hidden',
                isDark ? 'bg-[#212121] ring-white/10 text-gray-200' : 'bg-white ring-black/5 text-gray-700'
              ]"
            >
              <!-- Dropdown Header -->
              <div class="px-3 py-3" :class="isDark ? 'border-b border-white/10' : 'border-b border-gray-100'">
                 <div class="font-bold text-sm">{{ auth.user.name }}</div>
                 <div class="text-[12px] opacity-70">{{ auth.user.email }}</div>
              </div>

              <!-- List Items -->
              <div class="p-1.5 space-y-0.5 text-sm">
                <button :class="['w-full flex items-center gap-3 px-2 py-2.5 rounded-xl transition-colors', isDark ? 'hover:bg-[#2f2f2f]' : 'hover:bg-gray-100']">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                     <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                  </svg>
                  <span>Upgrade plan</span>
                </button>
                <button @click="showSettingsModal = true; activeSettingsTab = 'personalization'; showUserMenu = false" :class="['w-full flex items-center gap-3 px-2 py-2.5 rounded-xl transition-colors', isDark ? 'hover:bg-[#2f2f2f]' : 'hover:bg-gray-100']">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                     <path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span>Personalization</span>
                </button>
                <button @click="showSettingsModal = true; activeSettingsTab = 'general'; showUserMenu = false" :class="['w-full flex items-center gap-3 px-2 py-2.5 rounded-xl transition-colors', isDark ? 'hover:bg-[#2f2f2f]' : 'hover:bg-gray-100']">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                     <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                     <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  <span>Settings</span>
                </button>
              </div>

              <div class="h-px w-full" :class="isDark ? 'bg-white/10' : 'bg-gray-100'"></div>

              <div class="p-1.5 space-y-0.5 text-sm">
                 <button :class="['w-full flex items-center justify-between px-2 py-2.5 rounded-xl transition-colors', isDark ? 'hover:bg-[#2f2f2f]' : 'hover:bg-gray-100']">
                   <div class="flex items-center gap-3">
                     <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                       <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                     </svg>
                     <span>Help</span>
                   </div>
                   <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                 </button>
                 <Link 
                   href="/logout" 
                   method="post" 
                   as="button"
                   :class="['w-full flex items-center gap-3 px-2 py-2.5 rounded-xl transition-colors text-red-500', isDark ? 'hover:bg-[#2f2f2f]' : 'hover:bg-red-50']"
                 >
                   <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                     <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                   </svg>
                   <span>Log out</span>
                 </Link>
              </div>
            </div>
          </Transition>

          <button 
            @click="showUserMenu = !showUserMenu"
            :class="['w-full flex flex-col px-3 py-2 rounded-xl transition-all', isDark ? 'hover:bg-[#212121]' : 'hover:bg-gray-100']"
          >
            <div class="flex items-center gap-3 w-full">
              <img v-if="auth.user.avatar" :src="auth.user.avatar" class="w-8 h-8 rounded-full shadow-sm" alt="Avatar" />
              <div v-else class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold text-xs uppercase">
                {{ auth.user.name.charAt(0) }}
              </div>
              <div class="flex-1 min-w-0 text-left">
                 <p class="text-sm font-semibold truncate leading-tight" :class="isDark ? 'text-white' : 'text-gray-900'">{{ auth.user.name }}</p>
                 <p class="text-[10px] truncate leading-tight mt-0.5" :class="isDark ? 'text-gray-400' : 'text-gray-500'">Free plan</p>
              </div>
              <div class="px-2 py-1 rounded-lg text-[10px] font-bold tracking-wider uppercase border" :class="isDark ? 'border-white/20 text-gray-300 bg-white/5' : 'border-gray-300 text-gray-600 bg-gray-50'">
                 Upgrade
              </div>
            </div>
          </button>
        </div>

        <!-- Login CTA -->
        <div v-else class="space-y-4 pt-2">
          <div :class="['px-1 space-y-1.5', isDark ? 'text-gray-300' : 'text-gray-600']">
            <h4 class="font-bold text-[14px]">Get responses tailored to you</h4>
            <p class="text-[12px] leading-snug opacity-90">Log in to get answers based on saved chats, plus create images and upload files.</p>
          </div>
          <button 
            @click="showLoginModal = true"
            :class="[
              'w-full flex items-center justify-center rounded-xl px-4 py-2.5 text-[14px] font-semibold transition-colors active:scale-[0.98]',
              isDark ? 'bg-[#212121] hover:bg-[#2f2f2f] text-white border border-[#2f2f2f]' : 'bg-white hover:bg-gray-50 text-gray-800 border border-gray-200 shadow-sm'
            ]"
          >
            Log in
          </button>
        </div>
      </div>
    </aside>

    <!-- Overlay for mobile sidebar -->
    <div
      v-if="sidebarOpen"
      @click="sidebarOpen = false"
      class="fixed inset-0 z-40 bg-black/50 md:hidden"
    />

    <!-- Main Chat Area -->
    <main class="flex h-full flex-1 flex-col relative overflow-hidden">
      <!-- Chat Header (Model Selector) -->
      <header
        :class="[
          'sticky top-0 z-30 flex h-14 items-center gap-4 border-b px-4 py-3 transition-colors',
          isDark ? 'bg-[#212121]/80 border-[#2f2f2f] backdrop-blur-xl' : 'bg-white/80 border-gray-200 backdrop-blur-xl shadow-sm'
        ]"
      >
        <!-- Mobile Sidebar Toggle -->
        <button @click="sidebarOpen = true" class="p-2 -ml-2 rounded-lg hover:bg-gray-500/10 md:hidden transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>

        <!-- Model Dropdown -->
        <div class="relative inline-block text-left model-trigger">
          <button
            @click="showModelDropdown = !showModelDropdown"
            :class="[
              'flex items-center gap-2 rounded-xl px-3 py-1.5 text-base font-bold transition-all active:scale-95',
              isDark ? 'hover:bg-white/10 text-white' : 'hover:bg-gray-100 text-gray-800'
            ]"
          >
            <span class="text-indigo-500">{{ selectedModel.icon }}</span>
            <span>{{ selectedModel.name }}</span>
            <svg
              xmlns="http://www.w3.org/2000/svg"
              :class="['w-4 h-4 transition-transform duration-200', showModelDropdown ? 'rotate-180' : '']"
              fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
          </button>

          <!-- Transparent Overlay for closing dropdown -->
          <div v-if="showModelDropdown" @click="showModelDropdown = false" class="fixed inset-0 z-40"></div>

          <!-- Dropdown Menu -->
          <Transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
          >
            <div
              v-if="showModelDropdown"
              :class="[
                'absolute left-0 mt-2 w-72 origin-top-left rounded-2xl p-2 shadow-2xl ring-1 z-50',
                isDark ? 'bg-[#2f2f2f] ring-white/10' : 'bg-white ring-black/5'
              ]"
            >
              <div v-for="model in models" :key="model.id" class="mb-1 last:mb-0">
                <button
                  @click="selectedModel = model; showModelDropdown = false"
                  :class="[
                    'flex w-full items-start gap-3 rounded-xl p-3 text-left transition-all',
                    selectedModel.id === model.id
                      ? (isDark ? 'bg-white/5 border border-indigo-500/30' : 'bg-indigo-50 border border-indigo-100')
                      : (isDark ? 'hover:bg-white/5' : 'hover:bg-gray-50')
                  ]"
                >
                  <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-xl" :class="isDark ? 'bg-white/5' : 'bg-gray-100'">
                    {{ model.icon }}
                  </div>
                  <div class="flex-1 overflow-hidden">
                    <div class="flex items-center gap-2">
                       <div class="font-bold text-sm" :class="isDark ? 'text-white' : 'text-gray-900'">{{ model.name }}</div>
                       <div v-if="selectedModel.id === model.id" class="w-1.5 h-1.5 rounded-full bg-indigo-500"></div>
                    </div>
                    <div class="text-[11px] leading-tight mt-0.5" :class="isDark ? 'text-gray-400' : 'text-gray-500'">
                      {{ model.description }}
                    </div>
                  </div>
                </button>
              </div>
            </div>
          </Transition>
        </div>

        <div class="flex-1"></div>

        <!-- Desktop Action Buttons -->
        <div class="flex items-center gap-2">
           <template v-if="!auth.user">
             <button 
               @click="showLoginModal = true" 
               :class="['px-4 py-2 rounded-full text-[14px] font-bold transition-colors', isDark ? 'hover:bg-[#2f2f2f] text-white' : 'hover:bg-gray-100 text-gray-800']"
             >
               Log in
             </button>
             <button 
               @click="showLoginModal = true" 
               :class="['px-4 py-2 rounded-full text-[14px] font-bold transition-colors shadow-sm', isDark ? 'bg-white text-black hover:bg-gray-200' : 'bg-black text-white hover:bg-gray-800']"
             >
               Sign up
             </button>
           </template>
           <button v-else @click="startNewChat" :class="['p-2 rounded-lg transition-colors', isDark ? 'hover:bg-white/10' : 'hover:bg-gray-100']">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
              </svg>
           </button>
        </div>
      </header>

      <!-- Scrollable Messages Area -->
      <div
        ref="messagesContainer"
        :class="[
          'flex-1 overflow-y-auto pt-10 pb-40 custom-scrollbar',
          isDark ? 'scrollbar-dark' : 'scrollbar-light'
        ]"
      >
        <!-- Welcome Screen -->
        <div v-if="!messages.length" class="flex h-full flex-col items-center justify-center text-center px-4 animate-fade-in">
           <div :class="['w-16 h-16 rounded-full flex items-center justify-center text-3xl mb-4 bg-indigo-500 shadow-xl shadow-indigo-500/20 text-white']">
             🤖
           </div>
           <h2 class="text-2xl font-bold mb-2 tracking-tight">How can I help you today?</h2>
           <p class="text-gray-500 max-w-sm mb-8">Ask about anything, I'm here to help.</p>

           <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 w-full max-w-xl">
              <button
                v-for="(p, i) in placeholders"
                :key="i"
                @click="sendPlaceholder(p.text)"
                :class="['p-4 rounded-xl border text-left text-sm transition-all',
                  isDark ? 'border-[#2f2f2f] bg-[#171717]/50 hover:bg-[#2f2f2f]' : 'border-gray-200 bg-gray-50 hover:bg-gray-100']"
              >
                {{ p.label }}
              </button>
           </div>
        </div>

        <!-- Messages -->
        <div v-else class="mx-auto max-w-4xl px-4 md:px-6 space-y-8 pb-10">
          <div
            v-for="(msg, idx) in messages"
            :key="msg.id"
            :class="['flex w-full animate-fade-in group', msg.role === 'user' ? 'justify-end' : 'justify-start']"
          >
            <!-- Message Wrapper -->
            <div :class="['flex flex-col max-w-[90%] md:max-w-[80%]', msg.role === 'user' ? 'items-end' : 'items-start']">
              
              <!-- Meta (Labels only, no icons) -->
              <div class="flex items-center gap-2 mb-1.5 px-1 font-bold text-[10px] uppercase tracking-wider text-gray-500">
                <span :class="['font-normal normal-case tracking-normal transition-opacity opacity-0 group-hover:opacity-100']">
                  {{ msg.timestamp }}
                </span>
              </div>

              <!-- Bubble/Content -->
              <div
                :class="[
                  'transition-all max-w-full',
                  msg.role === 'user'
                    ? (isDark ? 'bg-[#2f2f2f] text-white ring-1 ring-white/5 rounded-2xl rounded-tr-none px-4 py-2.5 shadow-md shrink-0' : 'bg-gray-100 text-gray-800 ring-1 ring-gray-200 rounded-2xl rounded-tr-none px-4 py-2.5 shadow-sm shrink-0')
                    : (isDark ? 'text-gray-200 py-2 w-full' : 'text-gray-800 py-2 w-full')
                ]"
              >
                <!-- Show Attachments if any -->
                <div v-if="msg.attachments && msg.attachments.length > 0" class="flex flex-wrap gap-2 mb-3">
                  <div v-for="(att, i) in msg.attachments" :key="i" class="relative group">
                    <div v-if="att.isImage" class="w-24 h-24 rounded-lg overflow-hidden border border-white/5 shadow-sm">
                      <img :src="att.url" :alt="att.name" class="w-full h-full object-cover cursor-pointer hover:scale-105 transition-transform" />
                    </div>
                    <div v-else class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-xs">
                       <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                       <span class="max-w-[100px] truncate text-gray-300">{{ att.name }}</span>
                    </div>
                  </div>
                </div>

                <div
                  v-html="renderMarkdown(msg.content)"
                  :class="[
                    'prose-chat prose-sm leading-relaxed max-w-none',
                    isDark ? 'text-gray-200' : 'text-gray-800'
                  ]"
                />
              </div>

              <!-- AI Actions (Below Response) -->
              <div v-if="msg.role !== 'user'" class="flex items-center gap-4 mt-2 px-1 opacity-0 group-hover:opacity-100 transition-all duration-200">
                <button @click="speakMessage(msg)" class="text-gray-500 hover:text-indigo-400 transition-colors" title="Speak">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
                    <path d="M3 18v-6a9 9 0 0 1 18 0v6"></path>
                    <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path>
                  </svg>
                </button>
                <button @click="copyToClipboard(msg.content)" class="text-gray-500 hover:text-indigo-400 transition-colors" title="Copy">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
                    <rect width="14" height="14" x="8" y="8" rx="2" ry="2" />
                    <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <!-- Typing Indicator (Simple style) -->
          <div v-if="isTyping" class="flex justify-start animate-fade-in mt-4">
             <div class="flex flex-col items-start">
               <div :class="['px-5 py-4 rounded-2xl rounded-tl-none ring-1', isDark ? 'bg-[#2f2f2f]/30 ring-white/5' : 'bg-white ring-gray-100 shadow-sm']">
                  <div class="flex gap-1.5">
                     <div class="w-1.5 h-1.5 rounded-full bg-gray-400 animate-bounce" style="animation-delay: 0s" />
                     <div class="w-1.5 h-1.5 rounded-full bg-gray-400 animate-bounce" style="animation-delay: 0.2s" />
                     <div class="w-1.5 h-1.5 rounded-full bg-gray-400 animate-bounce" style="animation-delay: 0.4s" />
                  </div>
               </div>
             </div>
          </div>
        </div>
      </div>

      <!-- Footer/Input Box -->
      <footer
        :class="[
          'absolute bottom-0 left-0 right-0 p-4 md:p-8',
          isDark ? 'bg-gradient-to-t from-[#212121] via-[#212121] to-transparent' : 'bg-gradient-to-t from-white via-white to-transparent'
        ]"
      >
        <div class="mx-auto max-w-3xl">
          <form
            @submit.prevent="sendMessage"
            :class="[
              'relative flex flex-col gap-2 rounded-3xl border p-3 shadow-lg transition-all',
              isFocused
                ? (isDark ? 'border-[#444] bg-[#2f2f2f] ring-1 ring-white/10' : 'border-indigo-300 bg-white ring-2 ring-indigo-100')
                : (isDark ? 'border-[#2f2f2f] bg-[#2f2f2f]/80 backdrop-blur-md' : 'border-gray-200 bg-white')
            ]"
          >
            <!-- File Previews -->
            <div v-if="attachedFiles.length > 0" class="flex flex-wrap gap-3 px-3 pt-2 mb-1">
              <div v-for="(file, index) in attachedFiles" :key="index" class="relative group">
                <div v-if="file.isImage" class="w-16 h-16 rounded-xl overflow-hidden border border-white/10 shadow-sm relative transition-transform hover:scale-105">
                  <img :src="file.preview" class="w-full h-full object-cover" />
                  <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                </div>
                <!-- Other files placeholder -->
                <div v-else-if="file.isDoc" class="w-16 h-16 flex flex-col items-center justify-center rounded-xl bg-white/5 border border-white/10 text-[10px] text-gray-400 p-1">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-1 text-gray-500"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                  <span class="truncate w-full text-center">{{ file.name }}</span>
                </div>
                
                <!-- Remove Button -->
                <button 
                  type="button"
                  @click.stop="removeFile(index)" 
                  class="absolute -top-2 -right-2 w-5 h-5 bg-gray-900 text-white rounded-full flex items-center justify-center border border-white/20 shadow-md opacity-0 group-hover:opacity-100 transition-all hover:bg-black"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
              </div>
            </div>

            <!-- Top Row: Textarea -->
            <textarea
              ref="inputRef"
              v-model="inputMessage"
              @keydown="handleKeydown"
              @focus="isFocused = true"
              @blur="isFocused = false"
              @input="autoResize"
              @paste="handlePaste"
              placeholder="Learn something new"
              rows="1"
              :class="[
                'w-full px-3 py-2 text-[15px] resize-none outline-none bg-transparent max-h-48 scrollbar-hide',
                isDark ? 'text-white' : 'text-gray-800'
              ]"
            />

            <!-- Bottom Row: Tools & Action -->
            <div class="flex items-center justify-between mt-1 px-1">
              <!-- Left Tools -->
              <div class="flex items-center gap-2 relative">
                <!-- Hidden Inputs -->
                <input type="file" ref="fileInputRef" @change="handleFileSelect" multiple class="hidden" />
                <input type="file" ref="cameraInputRef" @change="handleFileSelect" accept="image/*" capture="environment" class="hidden" />

                <!-- Plus Button -->
                <button 
                  type="button" 
                  @click="toggleAttachmentMenu"
                  :class="[
                    'p-2 rounded-full transition-all duration-200 attachment-trigger',
                    showAttachmentMenu 
                      ? (isDark ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-900 shadow-inner') 
                      : (isDark ? 'hover:bg-white/5 text-gray-400' : 'hover:bg-gray-100 text-gray-500')
                  ]"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                </button>

                <!-- Attachment Menu -->
                <Transition name="menu-fade">
                  <div 
                    v-if="showAttachmentMenu"
                    class="absolute bottom-full left-0 mb-3 w-64 rounded-2xl p-1 shadow-2xl z-50 overflow-hidden backdrop-blur-xl border attachment-trigger"
                    :class="isDark ? 'bg-[#2f2f2f]/95 text-white border-white/10' : 'bg-white/95 text-gray-800 border-gray-200'"
                  >
                    <div class="flex flex-col">
                      <button @click="triggerFileUpload" :class="['flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors text-left group', isDark ? 'hover:bg-white/10' : 'hover:bg-gray-100']">
                        <div :class="['p-1.5 rounded-lg transition-colors', isDark ? 'bg-white/5 group-hover:bg-white/10' : 'bg-gray-100 group-hover:bg-gray-200']">
                          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.51a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>
                        </div>
                        <span class="text-sm font-medium">Add photos & files</span>
                      </button>

                      <button @click="triggerCamera" :class="['flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors text-left group', isDark ? 'hover:bg-white/10' : 'hover:bg-gray-100']">
                        <div :class="['p-1.5 rounded-lg transition-colors', isDark ? 'bg-white/5 group-hover:bg-white/10' : 'bg-gray-100 group-hover:bg-gray-200']">
                          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                        </div>
                        <span class="text-sm font-medium">Take photo</span>
                      </button>

                      <div :class="['h-px my-1 mx-2', isDark ? 'bg-white/5' : 'bg-gray-100']"></div>

                      <button :class="['flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors text-left group', isDark ? 'hover:bg-white/10' : 'hover:bg-gray-100']">
                        <div :class="['p-1.5 rounded-lg transition-colors', isDark ? 'bg-white/5 group-hover:bg-white/10' : 'bg-gray-100 group-hover:bg-gray-200']">
                          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect><circle cx="9" cy="9" r="2"></circle><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"></path></svg>
                        </div>
                        <span class="text-sm font-medium">Create image</span>
                      </button>

                      <button :class="['flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors text-left group', isDark ? 'hover:bg-white/10' : 'hover:bg-gray-100']">
                        <div :class="['p-1.5 rounded-lg transition-colors', isDark ? 'bg-white/5 group-hover:bg-white/10' : 'bg-gray-100 group-hover:bg-gray-200']">
                          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"></path><path d="M9 18h6"></path><path d="M10 22h4"></path></svg>
                        </div>
                        <span class="text-sm font-medium">Thinking</span>
                      </button>

                      <button :class="['flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors text-left group', isDark ? 'hover:bg-white/10' : 'hover:bg-gray-100']">
                        <div :class="['p-1.5 rounded-lg transition-colors', isDark ? 'bg-white/5 group-hover:bg-white/10' : 'bg-gray-100 group-hover:bg-gray-200']">
                          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></svg>
                        </div>
                        <span class="text-sm font-medium">Deep research</span>
                      </button>

                      <button :class="['flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors text-left group', isDark ? 'hover:bg-white/10' : 'hover:bg-gray-100']">
                        <div :class="['p-1.5 rounded-lg transition-colors', isDark ? 'bg-white/5 group-hover:bg-white/10 text-amber-500' : 'bg-gray-100 group-hover:bg-gray-200 text-amber-600']">
                          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path><path d="M3 6h18"></path><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                        </div>
                        <span class="text-sm font-medium">Shopping research</span>
                      </button>

                      <div class="h-px bg-white/5 my-1 mx-2"></div>

                      <button class="flex items-center justify-between px-3 py-2.5 rounded-xl hover:bg-white/10 transition-colors text-left group">
                        <div class="flex items-center gap-3">
                          <div class="p-1.5 rounded-lg bg-white/5 group-hover:bg-white/10">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>
                          </div>
                          <span class="text-sm font-medium">More</span>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-500"><path d="m9 18 6-6-6-6"></path></svg>
                      </button>
                    </div>
                  </div>
                </Transition>

                <button type="button" :class="['flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-medium transition-colors border',
                   isDark ? 'border-white/10 hover:bg-white/5 text-gray-300' : 'border-gray-200 hover:bg-gray-50 text-gray-600']">
                   <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-indigo-400"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2L20 2v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                   Study
                </button>
              </div>

              <!-- Right Tools & Send -->
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  @click="toggleVoiceInput"
                  :class="['p-2 rounded-full transition-all relative',
                    isListening ? `${activeAccentColor.bg} ${activeAccentColor.text} shadow-lg ${activeAccentColor.shadow}` : 'hover:bg-white/5 text-gray-500']"
                >
                  <div v-if="isListening" :class="['absolute inset-0 rounded-full animate-ping opacity-25', activeAccentColor.bg]"></div>
                  <svg v-if="!isListening" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"></path><path d="M19 10v2a7 7 0 0 1-14 0v-2"></path><line x1="12" y1="19" x2="12" y2="22"></line></svg>
                  <div v-else class="flex gap-0.5 items-center justify-center h-5 relative z-10 font-bold">
                    <div class="w-1 bg-white animate-voice-bar h-2"></div>
                    <div class="w-1 bg-white animate-voice-bar h-4" style="animation-delay: 0.1s"></div>
                    <div class="w-1 bg-white animate-voice-bar h-5" style="animation-delay: 0.2s"></div>
                    <div class="w-1 bg-white animate-voice-bar h-3" style="animation-delay: 0.3s"></div>
                  </div>
                </button>

                <!-- Voice Mode Button (Headphones) -->
                <button
                  type="button"
                  @click="openVoiceMode"
                  class="p-2 rounded-full transition-all hover:bg-white/5 text-gray-500"
                  title="Start Voice Session"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 18v-6a9 9 0 0 1 18 0v6"></path>
                    <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path>
                  </svg>
                </button>
                
                <button
                  type="submit"
                  :disabled="!inputMessage.trim() || isTyping"
                  :class="[
                    'flex h-9 w-9 shrink-0 items-center justify-center rounded-full transition-all',
                    inputMessage.trim() && !isTyping
                      ? 'bg-white text-black hover:bg-gray-200 shadow-md'
                      : 'bg-gray-200 text-gray-400 dark:bg-white/10 dark:text-gray-600'
                  ]"
                >
                  <svg v-if="!isTyping" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="translate-x-0.5"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
                  <div v-else class="w-4 h-4 rounded-full border-2 border-current border-t-transparent animate-spin" />
                </button>
              </div>
            </div>
          </form>
          <p class="mt-3 text-center text-[10px] text-gray-500">
              ChatGPT can make mistakes. Check important info.
          </p>
        </div>
      </footer>

      <!-- Real-time Voice Mode Overlay -->
      <Transition
        enter-active-class="transition-all duration-500 ease-out"
        enter-from-class="opacity-0 translate-y-8 scale-95"
        enter-to-class="opacity-100 translate-y-0 scale-100"
        leave-active-class="transition-all duration-300 ease-in"
        leave-from-class="opacity-100 translate-y-0 scale-100"
        leave-to-class="opacity-0 translate-y-8 scale-95"
      >
        <div v-if="showVoiceMode" class="fixed inset-0 z-200 flex flex-col items-center justify-between p-8" :class="isDark ? 'bg-[#171717] text-white' : 'bg-white text-gray-900'">
          <!-- Top area: Close Button -->
          <div class="w-full flex justify-end max-w-4xl mx-auto">
            <button @click="closeVoiceMode" class="p-3 rounded-full hover:bg-gray-500/10 transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
          </div>

          <!-- Middle area: Animated Orb & Status -->
          <div class="flex-1 flex flex-col items-center justify-center w-full">
            <div class="relative w-48 h-48 sm:w-64 sm:h-64 flex items-center justify-center mb-12">
              <!-- Animated background glowing orb -->
              <div class="absolute inset-0 rounded-full blur-3xl opacity-30 animate-pulse transition-all duration-700"
                   :class="[voiceModeState === 'speaking' ? activeAccentColor.bg : (voiceModeState === 'thinking' ? 'bg-white' : 'bg-gray-400')]"></div>
              
              <!-- Main interactive center -->
              <div class="relative w-32 h-32 sm:w-40 sm:h-40 rounded-full flex items-center justify-center shadow-2xl transition-all duration-500 transform"
                   :class="[
                     voiceModeState === 'listening' ? 'scale-110 bg-gray-800' : '',
                     voiceModeState === 'thinking' ? 'scale-100 bg-white shadow-white/30' : '',
                     voiceModeState === 'speaking' ? `scale-105 ${activeAccentColor.bg} ${activeAccentColor.shadow}` : '',
                     voiceModeState === 'idle' ? 'scale-100 bg-gray-700' : '',
                   ]">
                 <!-- Listening indicator -->
                 <svg v-if="voiceModeState === 'listening' || voiceModeState === 'idle'" xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-white opacity-80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"></path><path d="M19 10v2a7 7 0 0 1-14 0v-2"></path><line x1="12" y1="19" x2="12" y2="22"></line></svg>
                 
                 <!-- Speaking bars -->
                 <div v-if="voiceModeState === 'speaking'" class="flex gap-1.5 items-center justify-center h-10 w-full relative z-10">
                    <div class="w-1.5 bg-white rounded-full animate-voice-bar h-4"></div>
                    <div class="w-1.5 bg-white rounded-full animate-voice-bar h-8" style="animation-delay: 0.1s"></div>
                    <div class="w-1.5 bg-white rounded-full animate-voice-bar h-10" style="animation-delay: 0.2s"></div>
                    <div class="w-1.5 bg-white rounded-full animate-voice-bar h-6" style="animation-delay: 0.3s"></div>
                    <div class="w-1.5 bg-white rounded-full animate-voice-bar h-8" style="animation-delay: 0.4s"></div>
                 </div>

                 <!-- Thinking spinner -->
                 <svg v-if="voiceModeState === 'thinking'" class="w-10 h-10 text-gray-900 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
              </div>
            </div>

            <!-- Status text & Optional Transcript -->
            <div class="h-24 flex flex-col items-center justify-start text-center transition-opacity duration-300 w-full max-w-2xl" :class="[voiceModeState === 'idle' ? 'opacity-0' : 'opacity-100']">
              <p class="text-lg font-medium tracking-wide mb-2" :class="voiceModeState === 'speaking' ? activeAccentColor.text : ''">
                {{ voiceModeState === 'listening' ? 'Listening...' : (voiceModeState === 'thinking' ? 'Thinking...' : 'AI is speaking...') }}
              </p>
              
              <!-- Real-time transcript when 'Separate Voice' is OFF -->
              <p v-if="!personalizationSettings.separateVoice && inputMessage" class="text-sm opacity-60 line-clamp-3">
                "{{ inputMessage }}"
              </p>
            </div>
          </div>

          <!-- Bottom controls (Mute / Pause) -->
          <div class="w-full flex justify-center max-w-4xl mx-auto mb-8">
            <button @click="toggleVoiceModePause" class="p-4 rounded-full transition-colors flex items-center justify-center shadow-lg"
                    :class="[voiceModeMuted ? 'bg-red-500 hover:bg-red-600 text-white' : (isDark ? 'bg-[#2f2f2f] hover:bg-[#3f3f3f] text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-900')]">
              <svg v-if="voiceModeMuted" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="1" y1="1" x2="23" y2="23"></line><path d="M9 9v3a3 3 0 0 0 5.12 2.12M15 9.34V4a3 3 0 0 0-5.94-.6"></path><path d="M17 16.95A7 7 0 0 1 5 12H3a9 9 0 0 0 11.53 8.53"></path><path d="M12 22v-3"></path></svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"></path><path d="M19 10v2a7 7 0 0 1-14 0v-2"></path><line x1="12" y1="19" x2="12" y2="22"></line></svg>
            </button>
          </div>
        </div>
      </Transition>
    </main>

     <!-- Confirmation Modal -->
     <Transition
       enter-active-class="transition duration-300 ease-out"
       enter-from-class="opacity-0"
       enter-to-class="opacity-100"
       leave-active-class="transition duration-200 ease-in"
       leave-from-class="opacity-100"
       leave-to-class="opacity-0"
     >
       <div v-if="confirmModal.show" class="fixed inset-0 z-100 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
         <Transition
           appear
           enter-active-class="transition duration-300 ease-out"
           enter-from-class="scale-95 opacity-0"
           enter-to-class="scale-100 opacity-100"
         >
           <div :class="['w-full max-w-sm rounded-3xl p-6 shadow-2xl border transition-all',
             isDark ? 'bg-[#212121] border-white/10 text-white' : 'bg-white border-gray-100 text-gray-800']">
             <div class="flex items-center gap-4 mb-4">
               <div class="w-12 h-12 rounded-2xl bg-red-500/10 flex items-center justify-center text-red-500">
                 <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                   <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                 </svg>
               </div>
               <h3 class="text-xl font-bold">Delete Chat?</h3>
             </div>
             <p class="text-gray-500 text-sm mb-8 leading-relaxed">
               This will permanently delete the conversation history. This action cannot be undone.
             </p>
             <div class="flex gap-3">
               <button
                 @click="confirmModal.show = false"
                 :class="['flex-1 py-3 rounded-xl text-sm font-semibold transition-all',
                   isDark ? 'bg-[#2f2f2f] hover:bg-[#3f3f3f]' : 'bg-gray-100 hover:bg-gray-200 text-gray-600']"
               >
                 Cancel
               </button>
               <button
                 @click="confirmDelete"
                 class="flex-1 py-3 bg-red-500 hover:bg-red-600 text-white rounded-xl text-sm font-semibold shadow-lg shadow-red-500/20 transition-all"
               >
                 Delete
               </button>
             </div>
           </div>
         </Transition>
        </div>
      </Transition>

      <!-- Camera Modal -->
      <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div v-if="showCameraModal" class="fixed inset-0 z-110 flex items-center justify-center p-4 bg-black/90 backdrop-blur-md">
           <div class="relative w-full max-w-2xl bg-[#171717] rounded-3xl overflow-hidden shadow-2xl border border-white/10">
              <!-- Close Button -->
              <button @click="closeCamera" class="absolute top-4 right-4 z-20 p-2 rounded-full bg-black/40 text-white hover:bg-black/60 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
              </button>

              <div class="aspect-video relative bg-black">
                <video ref="cameraVideoRef" autoplay playsinline class="w-full h-full object-cover"></video>
                
                <!-- Guidelines -->
                <div class="absolute inset-0 border-2 border-white/10 pointer-events-none flex items-center justify-center">
                  <div class="w-48 h-48 border border-white/20 rounded-full opacity-20"></div>
                </div>
              </div>

              <div class="p-6 flex items-center justify-center gap-6">
                <button @click="closeCamera" class="text-gray-400 hover:text-white transition-colors text-sm font-medium">Cancel</button>
                <button @click="capturePhoto" class="group relative flex items-center justify-center">
                   <div class="absolute inset-0 bg-white rounded-full animate-ping opacity-20 group-hover:opacity-40 transition-opacity"></div>
                   <div class="w-16 h-16 rounded-full bg-white flex items-center justify-center text-black shadow-xl relative z-10 hover:scale-105 active:scale-95 transition-transform">
                      <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                   </div>
                </button>
                <div class="w-12"></div><!-- spacer -->
              </div>
           </div>
        </div>
      </Transition>

      <!-- Login Modal -->
      <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0 translate-y-4"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-4"
      >
        <div v-if="showLoginModal" class="fixed inset-0 z-120 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
           <div :class="['relative w-full max-w-[400px] rounded-3xl p-8 shadow-2xl transition-colors', isDark ? 'bg-[#171717] border border-[#2f2f2f]' : 'bg-white border border-gray-200']">
              <!-- Close Button -->
              <button @click="showLoginModal = false" :class="['absolute top-4 right-4 p-2 rounded-full transition-colors', isDark ? 'text-gray-400 hover:bg-[#212121] hover:text-white' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-900']">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>

              <!-- Title -->
              <div class="text-center space-y-2 mb-8">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-indigo-600 text-white shadow-lg shadow-indigo-500/20 mb-2">
                   <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                   </svg>
                </div>
                <!-- Dynamic Title based on Auth Step -->
                <h3 class="text-2xl font-bold tracking-tight" :class="isDark ? 'text-white' : 'text-gray-900'">
                   {{ authStep === 'email' ? 'Welcome back' : (authStep === 'password' && !isExistingUser ? 'Create your account' : (authStep === 'password' && isExistingUser ? 'Enter your password' : 'Enter Verification Code')) }}
                </h3>
              </div>

              <!-- Back button inside Modal (when not on email step) -->
              <button 
                v-if="authStep !== 'email'"
                @click="authStep = authStep === 'otp' ? 'password' : 'email'" 
                :class="['absolute top-4 left-4 p-2 rounded-full transition-colors', isDark ? 'text-gray-400 hover:bg-[#212121] hover:text-white' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-900']"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
              </button>

              <div v-if="authError" class="mb-4 p-3 bg-red-500/10 text-red-500 rounded-lg text-sm text-center">
                 {{ authError }}
              </div>

              <!-- Step 1: Email Input -->
              <div v-if="authStep === 'email'" class="space-y-4">
                 <input 
                    type="email" 
                    v-model="emailInput" 
                    placeholder="Email address"
                    @keyup.enter="handleEmailSubmit"
                    :class="['w-full px-4 py-3 rounded-xl border focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all font-medium', isDark ? 'bg-[#212121] border-[#2f2f2f] text-white focus:border-indigo-500' : 'bg-white border-gray-200 text-gray-900 focus:border-indigo-500']"
                 />
                 <button 
                   @click="handleEmailSubmit" 
                   :disabled="isAuthLoading"
                   class="w-full py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold transition-all disabled:opacity-50"
                 >
                   {{ isAuthLoading ? 'Please wait...' : 'Continue' }}
                 </button>

                 <div class="relative flex items-center py-2">
                    <div class="grow border-t" :class="isDark ? 'border-[#2f2f2f]' : 'border-gray-200'"></div>
                    <span class="shrink-0 mx-4 text-xs uppercase font-medium" :class="isDark ? 'text-gray-500' : 'text-gray-400'">OR</span>
                    <div class="grow border-t" :class="isDark ? 'border-[#2f2f2f]' : 'border-gray-200'"></div>
                 </div>

                 <!-- Socials -->
                 <div class="space-y-3">
                   <a href="/auth/google/redirect" :class="['flex w-full items-center justify-center gap-3 border font-medium py-3 rounded-xl transition-all shadow-sm group active:scale-[0.98]', isDark ? 'bg-[#212121] border-[#2f2f2f] hover:bg-[#2f2f2f] text-gray-200' : 'bg-white border-gray-200 hover:bg-gray-50 text-gray-700']">
                     <img src="https://www.gstatic.com/images/branding/product/1x/googleg_48dp.png" alt="Google" class="w-5 h-5 grayscale group-hover:grayscale-0 transition-all" />
                     <span>Continue with Google</span>
                   </a>
                   <a href="/auth/github/redirect" :class="['flex w-full items-center justify-center gap-3 border font-medium py-3 rounded-xl transition-all shadow-sm group active:scale-[0.98]', isDark ? 'bg-[#212121] border-[#2f2f2f] hover:bg-[#2f2f2f] text-gray-200' : 'bg-white border-gray-200 hover:bg-gray-50 text-gray-700']">
                     <svg class="w-5 h-5 fill-current grayscale group-hover:grayscale-0 transition-all" :class="isDark ? 'text-white' : 'text-gray-900'" viewBox="0 0 24 24"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>
                     <span>Continue with GitHub</span>
                   </a>
                   <a href="/auth/facebook/redirect" :class="['flex w-full items-center justify-center gap-3 border font-medium py-3 rounded-xl transition-all shadow-sm group active:scale-[0.98]', isDark ? 'bg-[#212121] border-[#2f2f2f] hover:bg-[#2f2f2f] text-gray-200' : 'bg-white border-gray-200 hover:bg-gray-50 text-gray-700']">
                     <svg class="w-5 h-5 fill-[#1877F2] grayscale group-hover:grayscale-0 transition-all" viewBox="0 0 24 24"><path d="M9.101 23.691v-7.98H6.627v-3.667h2.474v-1.58c0-4.03 2.302-5.965 5.486-5.965 1.524 0 3.111.272 3.111.272v3.42h-1.752c-2.016 0-2.643 1.242-2.643 2.522v1.332h3.854l-.616 3.667h-3.238v7.98h-3.754z"/></svg>
                     <span>Continue with Facebook</span>
                   </a>
                 </div>
              </div>

              <!-- Step 2: Password Input -->
              <div v-if="authStep === 'password'" class="space-y-4">
                 <div class="p-3 border rounded-xl flex items-center justify-between" :class="isDark ? 'border-[#2f2f2f] bg-[#212121] text-gray-300' : 'border-gray-200 bg-gray-50 text-gray-600'">
                    <span class="text-sm truncate">{{ emailInput }}</span>
                    <button @click="authStep = 'email'" class="text-indigo-500 text-sm font-bold hover:underline">Edit</button>
                 </div>
                 <input 
                    type="password" 
                    v-model="passwordInput" 
                    placeholder="Password"
                    @keyup.enter="isExistingUser ? handleLogin() : handleRegisterSubmit()"
                    :class="['w-full px-4 py-3 rounded-xl border focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all font-medium', isDark ? 'bg-[#212121] border-[#2f2f2f] text-white focus:border-indigo-500' : 'bg-white border-gray-200 text-gray-900 focus:border-indigo-500']"
                 />
                 <button 
                   @click="isExistingUser ? handleLogin() : handleRegisterSubmit()" 
                   :disabled="isAuthLoading"
                   class="w-full py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold transition-all disabled:opacity-50"
                 >
                   {{ isAuthLoading ? 'Please wait...' : 'Continue' }}
                 </button>
              </div>

              <!-- Step 3: OTP Input -->
              <div v-if="authStep === 'otp'" class="space-y-4">
                 <p class="text-sm text-center mb-4" :class="isDark ? 'text-gray-400' : 'text-gray-600'">
                    We just sent a code to <span class="font-bold text-indigo-500">{{ emailInput }}</span>. Enter it below to verify your email.
                 </p>
                 <input 
                    type="text" 
                    v-model="otpInput" 
                    placeholder="6-digit code"
                    maxlength="6"
                    @keyup.enter="handleVerifyOtp"
                    :class="['w-full px-4 py-3 rounded-xl border text-center tracking-[1em] text-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all font-bold', isDark ? 'bg-[#212121] border-[#2f2f2f] text-white focus:border-indigo-500' : 'bg-white border-gray-200 text-gray-900 focus:border-indigo-500']"
                 />
                 <button 
                   @click="handleVerifyOtp" 
                   :disabled="isAuthLoading"
                   class="w-full py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold transition-all disabled:opacity-50 mt-4"
                 >
                   {{ isAuthLoading ? 'Verifying...' : 'Verify & Create Account' }}
                 </button>
              </div>
              
              <p v-if="authStep === 'email'" class="text-center text-[12px] mt-6" :class="isDark ? 'text-gray-500' : 'text-gray-400'">
                 By continuing, you agree to our <a href="#" class="underline hover:text-indigo-400">Terms of Service</a>.
              </p>
           </div>
        </div>
      </Transition>

      <!-- Settings / Personalization Modal -->
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
      >
        <div v-if="showSettingsModal" class="fixed inset-0 z-[130] flex items-center justify-center p-4 sm:p-6 bg-black/60 backdrop-blur-sm">
          <div :class="['relative flex w-full max-w-[850px] h-[80vh] min-h-[500px] rounded-[18px] overflow-hidden shadow-2xl transition-colors', isDark ? 'bg-[#212121] border border-white/10' : 'bg-white border border-gray-200']">
            
            <!-- Close Button -->
            <button @click="showSettingsModal = false" :class="['absolute top-4 right-4 z-20 p-1.5 rounded-full transition-colors', isDark ? 'text-gray-400 hover:bg-[#2f2f2f] hover:text-white' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-900']">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>

            <!-- Sidebar -->
            <div :class="['w-[240px] flex-shrink-0 flex flex-col pt-4 pb-4 border-r h-full overflow-y-auto custom-scrollbar', isDark ? 'bg-[#171717] border-[#2f2f2f] scrollbar-dark' : 'bg-gray-50/50 border-gray-200 scrollbar-light']">
              <div class="px-3 space-y-1">
                <button @click="activeSettingsTab = 'general'" :class="['w-full flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium transition-colors', activeSettingsTab === 'general' ? (isDark ? 'bg-[#2f2f2f] text-gray-200' : 'bg-gray-200 text-gray-900') : (isDark ? 'text-gray-400 hover:bg-[#212121] hover:text-gray-300' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-800')]">
                   <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                     <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                     <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                   </svg>
                   General
                </button>
                <button @click="activeSettingsTab = 'notifications'" :class="['w-full flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium transition-colors', activeSettingsTab === 'notifications' ? (isDark ? 'bg-[#2f2f2f] text-gray-200' : 'bg-gray-200 text-gray-900') : (isDark ? 'text-gray-400 hover:bg-[#212121] hover:text-gray-300' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-800')]">
                   <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                   Notifications
                </button>
                <button @click="activeSettingsTab = 'personalization'" :class="['w-full flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium transition-colors', activeSettingsTab === 'personalization' ? (isDark ? 'bg-[#2f2f2f] text-gray-200' : 'bg-gray-200 text-gray-900') : (isDark ? 'text-gray-400 hover:bg-[#212121] hover:text-gray-300' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-800')]">
                   <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                     <path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                   </svg>
                   Personalization
                </button>
                <button @click="activeSettingsTab = 'apps'" :class="['w-full flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium transition-colors', activeSettingsTab === 'apps' ? (isDark ? 'bg-[#2f2f2f] text-gray-200' : 'bg-gray-200 text-gray-900') : (isDark ? 'text-gray-400 hover:bg-[#212121] hover:text-gray-300' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-800')]">
                   <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                   Apps
                </button>
                <button @click="activeSettingsTab = 'data_controls'" :class="['w-full flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium transition-colors', activeSettingsTab === 'data_controls' ? (isDark ? 'bg-[#2f2f2f] text-gray-200' : 'bg-gray-200 text-gray-900') : (isDark ? 'text-gray-400 hover:bg-[#212121] hover:text-gray-300' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-800')]">
                   <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                   Data controls
                </button>
                <button @click="activeSettingsTab = 'security'" :class="['w-full flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium transition-colors', activeSettingsTab === 'security' ? (isDark ? 'bg-[#2f2f2f] text-gray-200' : 'bg-gray-200 text-gray-900') : (isDark ? 'text-gray-400 hover:bg-[#212121] hover:text-gray-300' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-800')]">
                   <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                   Security
                </button>
              </div>
            </div>

            <!-- Content Area -->
            <div :class="['flex-1 relative overflow-y-auto custom-scrollbar', isDark ? 'text-gray-200 scrollbar-dark' : 'text-gray-700 scrollbar-light']">
              
              <!-- Tab: General -->
              <div v-if="activeSettingsTab === 'general'" class="p-6 sm:p-8 max-w-[600px] mx-auto min-h-full pb-16">
                 <h2 class="text-xl font-bold border-b pb-4 mb-6 sticky top-0" :class="isDark ? 'text-white border-[#2f2f2f] bg-[#212121]/95 backdrop-blur-md pt-6 -mt-6 z-10' : 'text-gray-900 border-gray-200 bg-white/95 backdrop-blur-md pt-6 -mt-6 z-10'">General</h2>
                 <div class="space-y-6">
                    <div class="flex items-center justify-between">
                       <div>
                          <span class="text-[15px] font-medium" :class="isDark ? 'text-gray-200' : 'text-gray-800'">Theme</span>
                       </div>
                       <select 
                         @change="(e) => isDark = e.target.value === 'Dark'"
                         :value="isDark ? 'Dark' : 'Light'"
                         :class="['text-[14px] rounded-lg border px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500 w-[140px]', isDark ? 'bg-[#212121] border-[#3f3f3f] text-white hover:bg-[#2f2f2f]' : 'bg-gray-50 border-gray-200 hover:bg-white']"
                       >
                         <option>System</option>
                         <option>Dark</option>
                         <option>Light</option>
                       </select>
                    </div>
                 </div>
              </div>

              <!-- Tab: Personalization -->
              <div v-if="activeSettingsTab === 'personalization'" class="p-6 sm:p-8 max-w-[600px] mx-auto min-h-full pb-16">
                 <h2 class="text-xl font-bold border-b pb-4 mb-6 sticky top-0" :class="isDark ? 'text-white border-[#2f2f2f] bg-[#212121]/95 backdrop-blur-md pt-6 -mt-6 z-10' : 'text-gray-900 border-gray-200 bg-white/95 backdrop-blur-md pt-6 -mt-6 z-10'">Personalization</h2>

                 <!-- Invisible Overlay for closing settings dropdowns -->
                 <div v-if="openDropdown" @click="openDropdown = null" class="fixed inset-0 z-40"></div>

                 <!-- Secure your account -->
                 <div class="relative p-5 rounded-xl border mb-6" :class="isDark ? 'border-[#2f2f2f] bg-[#171717]' : 'border-gray-200 bg-gray-50'">
                   <!-- Close icon top right -->
                   <button class="absolute top-4 right-4 transition-opacity" :class="isDark ? 'text-gray-500 hover:text-white' : 'text-gray-400 hover:text-black'">
                     <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                   </button>
                   <div class="flex items-center gap-2 mb-2">
                     <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                     <span class="font-bold text-[14px]" :class="isDark ? 'text-gray-200' : 'text-gray-800'">Secure your account</span>
                   </div>
                   <p class="text-[13px] mb-4 pr-6 leading-relaxed" :class="isDark ? 'text-gray-400' : 'text-gray-500'">Add multi-factor authentication (MFA), like a passkey or text message, to help protect your account when logging in.</p>
                   <button :class="['px-4 py-1.5 rounded-full border text-[13px] font-medium transition-colors', isDark ? 'border-[#3f3f3f] text-gray-200 hover:bg-[#2f2f2f]' : 'border-gray-300 text-gray-800 hover:bg-white']">Set up MFA</button>
                 </div>

                 <div class="space-y-0 text-[14px]">
                    <!-- Appearance -->
                    <div class="flex items-center justify-between py-4 border-b" :class="isDark ? 'border-[#2f2f2f]' : 'border-gray-200'">
                       <span :class="isDark ? 'text-gray-200' : 'text-gray-800'">Appearance</span>
                       <div class="relative w-[140px]">
                         <button @click="openDropdown = openDropdown === 'appearance' ? null : 'appearance'" :class="['w-full flex items-center justify-between text-[14px] rounded-lg border px-3 py-2 cursor-pointer transition-colors focus:ring-1 focus:ring-indigo-500', isDark ? 'bg-[#212121] border-[#3f3f3f] text-white hover:bg-[#2f2f2f]' : 'bg-gray-50 border-gray-200 hover:bg-white']">
                           <span>{{ personalizationSettings.appearance }}</span>
                           <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                         </button>
                         <div v-if="openDropdown === 'appearance'" class="absolute z-50 right-0 mt-2 w-[160px] rounded-xl shadow-xl py-1.5 border" :class="isDark ? 'bg-[#2f2f2f] border-[#3f3f3f]' : 'bg-white border-gray-200'">
                            <button v-for="opt in ['System', 'Dark', 'Light']" :key="opt" @click="personalizationSettings.appearance = opt; isDark = (opt === 'Dark'); openDropdown = null" class="w-full flex items-center justify-between px-3 py-2 text-[14px] transition-colors" :class="isDark ? 'text-gray-200 hover:bg-[#3f3f3f]' : 'text-gray-800 hover:bg-gray-100'">
                               <span>{{ opt }}</span>
                               <svg v-if="personalizationSettings.appearance === opt" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </button>
                         </div>
                       </div>
                    </div>

                    <!-- Accent color -->
                    <div class="flex items-center justify-between py-4 border-b" :class="isDark ? 'border-[#2f2f2f]' : 'border-gray-200'">
                       <span :class="isDark ? 'text-gray-200' : 'text-gray-800'">Accent color</span>
                       <div class="relative w-[140px]">
                          <button @click="openDropdown = openDropdown === 'accentColor' ? null : 'accentColor'" :class="['w-full flex items-center justify-between text-[14px] rounded-lg border px-3 py-2 cursor-pointer transition-colors focus:ring-1 focus:ring-indigo-500', isDark ? 'bg-[#212121] border-[#3f3f3f] text-white hover:bg-[#2f2f2f]' : 'bg-gray-50 border-gray-200 hover:bg-white']">
                             <div class="flex items-center gap-2.5">
                               <div class="w-2.5 h-2.5 rounded-full" :class="accentColors.find(c => c.name === personalizationSettings.accentColor)?.color || 'bg-green-500'"></div>
                               <span>{{ personalizationSettings.accentColor }}</span>
                             </div>
                             <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                          </button>
                          <div v-if="openDropdown === 'accentColor'" class="absolute z-50 right-0 mt-2 w-[160px] rounded-xl shadow-xl py-1.5 border" :class="isDark ? 'bg-[#2f2f2f] border-[#3f3f3f]' : 'bg-white border-gray-200'">
                             <button v-for="colorOpt in accentColors" :key="colorOpt.name" @click="personalizationSettings.accentColor = colorOpt.name; openDropdown = null" class="w-full flex items-center justify-between px-3 py-2 text-[14px] transition-colors" :class="isDark ? 'text-gray-200 hover:bg-[#3f3f3f]' : 'text-gray-800 hover:bg-gray-100'">
                                <div class="flex items-center gap-2.5">
                                  <div class="w-2.5 h-2.5 rounded-full" :class="colorOpt.color"></div>
                                  <span>{{ colorOpt.name }}</span>
                                </div>
                                <svg v-if="personalizationSettings.accentColor === colorOpt.name" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                             </button>
                          </div>
                       </div>
                    </div>

                    <!-- Language -->
                    <div class="flex items-center justify-between py-4 border-b" :class="isDark ? 'border-[#2f2f2f]' : 'border-gray-200'">
                       <span :class="isDark ? 'text-gray-200' : 'text-gray-800'">Language</span>
                       <div class="relative w-[140px]">
                         <button @click="openDropdown = openDropdown === 'language' ? null : 'language'" :class="['w-full flex items-center justify-between text-[14px] rounded-lg border px-3 py-2 cursor-pointer transition-colors focus:ring-1 focus:ring-indigo-500', isDark ? 'bg-[#212121] border-[#3f3f3f] text-white hover:bg-[#2f2f2f]' : 'bg-gray-50 border-gray-200 hover:bg-white']">
                           <span>{{ personalizationSettings.language }}</span>
                           <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                         </button>
                         <div v-if="openDropdown === 'language'" class="absolute z-50 right-0 mt-2 w-[160px] rounded-xl shadow-xl py-1.5 border" :class="isDark ? 'bg-[#2f2f2f] border-[#3f3f3f]' : 'bg-white border-gray-200'">
                            <button v-for="opt in ['Auto-detect', 'English']" :key="opt" @click="personalizationSettings.language = opt; openDropdown = null" class="w-full flex items-center justify-between px-3 py-2 text-[14px] transition-colors" :class="isDark ? 'text-gray-200 hover:bg-[#3f3f3f]' : 'text-gray-800 hover:bg-gray-100'">
                               <span>{{ opt }}</span>
                               <svg v-if="personalizationSettings.language === opt" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </button>
                         </div>
                       </div>
                    </div>

                    <!-- Spoken language -->
                    <div class="py-4 border-b" :class="isDark ? 'border-[#2f2f2f]' : 'border-gray-200'">
                       <div class="flex items-center justify-between mb-1.5">
                          <span :class="isDark ? 'text-gray-200' : 'text-gray-800'">Spoken language</span>
                          <div class="relative w-[140px]">
                            <button @click="openDropdown = openDropdown === 'spokenLanguage' ? null : 'spokenLanguage'" :class="['w-full flex items-center justify-between text-[14px] rounded-lg border px-3 py-2 cursor-pointer transition-colors focus:ring-1 focus:ring-indigo-500', isDark ? 'bg-[#212121] border-[#3f3f3f] text-white hover:bg-[#2f2f2f]' : 'bg-gray-50 border-gray-200 hover:bg-white']">
                              <span>{{ personalizationSettings.spokenLanguage }}</span>
                              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div v-if="openDropdown === 'spokenLanguage'" class="absolute z-50 right-0 mt-2 w-[160px] rounded-xl shadow-xl py-1.5 border" :class="isDark ? 'bg-[#2f2f2f] border-[#3f3f3f]' : 'bg-white border-gray-200'">
                               <button v-for="opt in ['Auto-detect', 'English']" :key="opt" @click="personalizationSettings.spokenLanguage = opt; openDropdown = null" class="w-full flex items-center justify-between px-3 py-2 text-[14px] transition-colors" :class="isDark ? 'text-gray-200 hover:bg-[#3f3f3f]' : 'text-gray-800 hover:bg-gray-100'">
                                  <span>{{ opt }}</span>
                                  <svg v-if="personalizationSettings.spokenLanguage === opt" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                               </button>
                            </div>
                          </div>
                       </div>
                       <p class="text-[12px] pr-20 leading-relaxed" :class="isDark ? 'text-gray-400' : 'text-gray-500'">*For best results, select the language you mainly speak. If it's not listed, it may still be supported via auto-detection.</p>
                    </div>

                    <!-- Voice -->
                    <div class="flex items-center justify-between py-4 border-b" :class="isDark ? 'border-[#2f2f2f]' : 'border-gray-200'">
                       <span :class="isDark ? 'text-gray-200' : 'text-gray-800'">Voice</span>
                       <div class="flex items-center gap-2">
                          <button @click="playTestVoice" :class="['flex items-center gap-1.5 px-3 py-2 rounded-lg border text-[13px] font-medium transition-colors', isDark ? 'border-[#3f3f3f] text-gray-300 hover:bg-[#2f2f2f] hover:text-white bg-[#2f2f2f]/50' : 'border-gray-300 text-gray-700 hover:bg-gray-100 bg-white']">
                             <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                             Play
                          </button>
                          
                          <div class="relative w-[140px]">
                            <button @click="openDropdown = openDropdown === 'voice' ? null : 'voice'" :class="['w-full flex items-center justify-between text-[14px] rounded-lg border px-3 py-2 cursor-pointer transition-colors focus:ring-1 focus:ring-indigo-500', isDark ? 'bg-[#212121] border-[#3f3f3f] text-white hover:bg-[#2f2f2f]' : 'bg-gray-50 border-gray-200 hover:bg-white']">
                              <span>{{ voices.find(v => v.name === personalizationSettings.voice)?.label || personalizationSettings.voice }}</span>
                              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div v-if="openDropdown === 'voice'" class="absolute z-50 right-0 mt-2 w-[160px] rounded-xl shadow-xl py-1.5 border" :class="isDark ? 'bg-[#2f2f2f] border-[#3f3f3f]' : 'bg-white border-gray-200'">
                               <button v-for="voice in voices" :key="voice.name" @click="personalizationSettings.voice = voice.name; openDropdown = null" class="w-full flex items-center justify-between px-3 py-2 text-[14px] transition-colors" :class="isDark ? 'text-gray-200 hover:bg-[#3f3f3f]' : 'text-gray-800 hover:bg-gray-100'">
                                  <span>{{ voice.label }}</span>
                                  <svg v-if="personalizationSettings.voice === voice.name" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                               </button>
                            </div>
                          </div>
                       </div>
                    </div>

                    <!-- Separate Voice -->
                    <div class="py-4 flex items-center justify-between">
                       <div class="pr-6">
                          <span class="block mb-1 font-medium" :class="isDark ? 'text-gray-200' : 'text-gray-800'">Separate Voice</span>
                          <p class="text-[12px]" :class="isDark ? 'text-gray-400' : 'text-gray-500'">Keep ChatGPT Voice in a separate full screen,<br>without real time transcripts and visuals.</p>
                       </div>
                       <button @click="personalizationSettings.separateVoice = !personalizationSettings.separateVoice" type="button" :class="['relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none', personalizationSettings.separateVoice ? 'bg-green-500' : (isDark ? 'bg-[#3f3f3f]' : 'bg-gray-300')]" role="switch" :aria-checked="personalizationSettings.separateVoice.toString()">
                          <span aria-hidden="true" :class="['pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out', personalizationSettings.separateVoice ? 'translate-x-5' : 'translate-x-0']"></span>
                       </button>
                    </div>
                 </div>
              </div>
              
              <!-- Tab: Data controls -->
              <div v-if="activeSettingsTab === 'data_controls'" class="p-6 sm:p-8 max-w-[600px] mx-auto min-h-full pb-16">
                 <h2 class="text-xl font-bold border-b pb-4 mb-6 sticky top-0" :class="isDark ? 'text-white border-[#2f2f2f] bg-[#212121]/95 backdrop-blur-md pt-6 -mt-6 z-10' : 'text-gray-900 border-gray-200 bg-white/95 backdrop-blur-md pt-6 -mt-6 z-10'">Data controls</h2>
                 
                 <div class="space-y-0 text-[14px]">
                    <!-- Improve the model for everyone -->
                    <div class="flex items-center justify-between py-4 border-b" :class="isDark ? 'border-[#2f2f2f]' : 'border-gray-200'">
                       <span :class="isDark ? 'text-gray-200' : 'text-gray-800'">Improve the model for everyone</span>
                       <div class="flex items-center gap-2 cursor-pointer transition-colors" :class="isDark ? 'text-gray-400 hover:text-white' : 'text-gray-500 hover:text-gray-900'">
                          <span>On</span>
                          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                       </div>
                    </div>

                    <!-- Shared links -->
                    <div class="flex items-center justify-between py-4 border-b" :class="isDark ? 'border-[#2f2f2f]' : 'border-gray-200'">
                       <span :class="isDark ? 'text-gray-200' : 'text-gray-800'">Shared links</span>
                       <button :class="['px-3 py-1.5 rounded-full border text-[13px] font-medium transition-colors', isDark ? 'border-[#3f3f3f] text-gray-300 hover:bg-[#2f2f2f] hover:text-white' : 'border-gray-300 text-gray-700 hover:bg-gray-100']">Manage</button>
                    </div>

                    <!-- Archived chats -->
                    <div class="flex items-center justify-between py-4 border-b" :class="isDark ? 'border-[#2f2f2f]' : 'border-gray-200'">
                       <span :class="isDark ? 'text-gray-200' : 'text-gray-800'">Archived chats</span>
                       <button :class="['px-3 py-1.5 rounded-full border text-[13px] font-medium transition-colors', isDark ? 'border-[#3f3f3f] text-gray-300 hover:bg-[#2f2f2f] hover:text-white' : 'border-gray-300 text-gray-700 hover:bg-gray-100']">Manage</button>
                    </div>

                    <!-- Archive all chats -->
                    <div class="flex items-center justify-between py-4 border-b" :class="isDark ? 'border-[#2f2f2f]' : 'border-gray-200'">
                       <span :class="isDark ? 'text-gray-200' : 'text-gray-800'">Archive all chats</span>
                       <button :class="['px-3 py-1.5 rounded-full border text-[13px] font-medium transition-colors', isDark ? 'border-[#3f3f3f] text-gray-300 hover:bg-[#2f2f2f] hover:text-white' : 'border-gray-300 text-gray-700 hover:bg-gray-100']">Archive all</button>
                    </div>

                    <!-- Delete all chats -->
                    <div class="flex items-center justify-between py-4 border-b" :class="isDark ? 'border-[#2f2f2f]' : 'border-gray-200'">
                       <span :class="isDark ? 'text-gray-200' : 'text-gray-800'">Delete all chats</span>
                       <button 
                         @click="deleteAllChats"
                         :class="['px-3 py-1.5 rounded-full border border-red-500/50 text-red-500 text-[13px] font-medium transition-colors hover:bg-red-500/10']"
                       >
                         Delete all
                       </button>
                    </div>

                    <!-- Export data -->
                    <div class="flex items-center justify-between py-4" :class="isDark ? 'border-[#2f2f2f]' : 'border-gray-200'">
                       <span :class="isDark ? 'text-gray-200' : 'text-gray-800'">Export data</span>
                       <button :class="['px-3 py-1.5 rounded-full border text-[13px] font-medium transition-colors', isDark ? 'border-[#3f3f3f] text-gray-300 hover:bg-[#2f2f2f] hover:text-white' : 'border-gray-300 text-gray-700 hover:bg-gray-100']">Export</button>
                    </div>
                 </div>
              </div>

              <!-- Tab: Placeholder for others -->
              <div v-if="!['general', 'personalization', 'data_controls'].includes(activeSettingsTab)" class="p-6 sm:p-8 max-w-[600px] mx-auto min-h-full pb-16 flex items-center justify-center">
                 <p class="text-sm opacity-50 uppercase tracking-wider font-bold">Coming Soon</p>
              </div>

            </div>
          </div>
        </div>
      </Transition>
    </div>
  </template>

<script setup>
import { ref, onMounted, nextTick, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { voices } from '../voices.js';

const props = defineProps({
    conversations: Array,
    activeConversationId: [Number, String],
    messages: Array,
    auth: Object
});

const isDark = ref(true);
const sidebarOpen = ref(false);
const showLoginModal = ref(false);
const showUserMenu = ref(false);
const showSettingsModal = ref(false);
const activeSettingsTab = ref('personalization');

// Voice Mode Refs
const showVoiceMode = ref(false);
const voiceModeState = ref('idle'); // 'idle', 'listening', 'thinking', 'speaking'
const voiceModeMuted = ref(false);

const openDropdown = ref(null);
const personalizationSettings = ref({
  appearance: 'Dark',
  accentColor: 'Green',
  language: 'Auto-detect',
  spokenLanguage: 'Auto-detect',
  voice: 'Sol',
  separateVoice: false
});
const accentColors = [
  { name: 'Default', color: 'bg-gray-400' },
  { name: 'Blue', color: 'bg-blue-500' },
  { name: 'Green', color: 'bg-green-500' },
  { name: 'Yellow', color: 'bg-yellow-500' },
  { name: 'Pink', color: 'bg-pink-500' },
  { name: 'Orange', color: 'bg-orange-500' },
];

const activeAccentColor = computed(() => {
  switch (personalizationSettings.value.accentColor) {
    case 'Blue': return { bg: 'bg-blue-500', text: 'text-white', shadow: 'shadow-blue-500/50' };
    case 'Green': return { bg: 'bg-green-500', text: 'text-white', shadow: 'shadow-green-500/50' };
    case 'Yellow': return { bg: 'bg-yellow-500', text: 'text-white', shadow: 'shadow-yellow-500/50' };
    case 'Pink': return { bg: 'bg-pink-500', text: 'text-white', shadow: 'shadow-pink-500/50' };
    case 'Orange': return { bg: 'bg-orange-500', text: 'text-white', shadow: 'shadow-orange-500/50' };
    default: return { bg: 'bg-gray-500', text: 'text-white', shadow: 'shadow-gray-500/50' };
  }
});

// Auth Flow Refs
const authStep = ref('email');
const emailInput = ref('');
const passwordInput = ref('');
const otpInput = ref('');
const isExistingUser = ref(false);
const isAuthLoading = ref(false);
const authError = ref('');

const resetAuth = () => {
    authStep.value = 'email';
    emailInput.value = '';
    passwordInput.value = '';
    otpInput.value = '';
    isExistingUser.value = false;
    authError.value = '';
    isAuthLoading.value = false;
};

watch(showLoginModal, (val) => {
    if (!val) resetAuth();
});

const handleEmailSubmit = async () => {
    if (!emailInput.value) return;
    isAuthLoading.value = true;
    authError.value = '';
    try {
        const res = await axios.post('/auth/check-email', { email: emailInput.value });
        isExistingUser.value = res.data.exists;
        authStep.value = 'password';
    } catch (e) {
        authError.value = e.response?.data?.message || 'Error checking email';
    } finally {
        isAuthLoading.value = false;
    }
};

const handleLogin = async () => {
    if (!passwordInput.value) return;
    isAuthLoading.value = true;
    authError.value = '';
    try {
        await axios.post('/auth/login', { email: emailInput.value, password: passwordInput.value });
        window.location.reload();
    } catch (e) {
        authError.value = e.response?.data?.error || 'Invalid credentials';
    } finally {
        isAuthLoading.value = false;
    }
};

const handleRegisterSubmit = async () => {
    if (!passwordInput.value) return;
    isAuthLoading.value = true;
    authError.value = '';
    try {
        await axios.post('/auth/register', { email: emailInput.value, password: passwordInput.value });
        authStep.value = 'otp';
    } catch (e) {
        authError.value = e.response?.data?.message || 'Error registering';
    } finally {
        isAuthLoading.value = false;
    }
};

const handleVerifyOtp = async () => {
    if (!otpInput.value) return;
    isAuthLoading.value = true;
    authError.value = '';
    try {
        await axios.post('/auth/verify', { email: emailInput.value, otp: otpInput.value });
        window.location.reload();
    } catch (e) {
        authError.value = e.response?.data?.error || 'Invalid OTP';
    } finally {
        isAuthLoading.value = false;
    }
};

const inputMessage = ref('');
const isTyping = ref(false);
const isFocused = ref(false);
const inputRef = ref(null);
const messagesContainer = ref(null);
const toast = ref({ show: false, message: '', type: 'success' });
const confirmModal = ref({ show: false, id: null });
const isEditing = ref(false);
const editingMessageId = ref(null);
const isListening = ref(false);
const showAttachmentMenu = ref(false);
const showCameraModal = ref(false);
const cameraVideoRef = ref(null);
const fileInputRef = ref(null);
const cameraInputRef = ref(null);
const attachedFiles = ref([]);
const showModelDropdown = ref(false);
const models = [
    { id: 'gemini-2.5-flash', name: 'Gemini 2.5 Flash', icon: '✨', description: 'Fast and smart, best for complex coding tasks' },
    { id: 'gemini-2.0-flash-lite', name: 'Gemini 2.0 Flash Lite', icon: '⚡', description: 'Ultra-fast, perfect for quick questions' },
    { id: 'llama-3.2-11b', name: 'Llama 3.2 Vision (Groq)', icon: '👁️', description: 'Groq vision model, can see and read images' },
    { id: 'llama-3.3-70b', name: 'Llama 3.3 70B (Groq)', icon: '🦙', description: 'Groq flagship model, extremely smart and versatile' },
    { id: 'llama-3.1-8b', name: 'Llama 3.1 8B (Groq)', icon: '⚡', description: 'High-speed model, perfect for instant answers' },
];
const selectedModel = ref(models[0]);

let recognition = null;
let cameraStream = null;

const activeConversation = computed(() =>
    props.conversations.find(c => c.id === props.activeConversationId)
);

const activeConversationTitle = computed(() => activeConversation.value?.title);

const placeholders = [
  { label: 'Explain async wait', text: 'Explain how async/await works in JavaScript.' },
  { label: 'Optimize my code', text: 'How can I optimize this PHP code? [paste your code]' },
  { label: 'Debug my CSS', text: 'Help me debug my CSS flexbox issue.' },
  { label: 'Write a regex', text: 'Write a regex to validate an email address.' }
];

onMounted(() => {
  const saved = localStorage.getItem('theme');
  if (saved) isDark.value = saved === 'dark';
  scrollToBottom();
  inputRef.value?.focus();

  // Close menus on click outside
  window.addEventListener('click', (e) => {
    if (showAttachmentMenu.value && !e.target.closest('.attachment-trigger')) {
      showAttachmentMenu.value = false;
    }
    if (showModelDropdown.value && !e.target.closest('.model-trigger')) {
      showModelDropdown.value = false;
    }
  });
});

function toggleDark() {
  isDark.value = !isDark.value;
  localStorage.setItem('theme', isDark.value ? 'dark' : 'light');
}

function showToast(message, type = 'success') {
  toast.value = { show: true, message, type };
  setTimeout(() => (toast.value.show = false), 3000);
}

function autoResize() {
  const el = inputRef.value;
  if (!el) return;
  el.style.height = 'auto';
  el.style.height = (el.scrollHeight > 192 ? 192 : el.scrollHeight) + 'px';
}

function handleKeydown(e) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendMessage();
  }
}

function toggleAttachmentMenu() {
  showAttachmentMenu.value = !showAttachmentMenu.value;
}

function triggerFileUpload() {
  fileInputRef.value?.click();
  showAttachmentMenu.value = false;
}

async function triggerCamera() {
  showAttachmentMenu.value = false;
  
  // Try native file capture first (best for mobile)
  if (/Android|iPhone|iPad/i.test(navigator.userAgent)) {
    cameraInputRef.value?.click();
    return;
  }

  // Fallback to desktop camera modal
  showCameraModal.value = true;
  await nextTick();
  startCamera();
}

async function startCamera() {
  try {
    cameraStream = await navigator.mediaDevices.getUserMedia({ 
      video: { facingMode: 'user' }, 
      audio: false 
    });
    if (cameraVideoRef.value) {
      cameraVideoRef.value.srcObject = cameraStream;
    }
  } catch (err) {
    console.error("Camera error:", err);
    showToast("Could not access camera", "error");
    closeCamera();
  }
}

function closeCamera() {
  if (cameraStream) {
    cameraStream.getTracks().forEach(track => track.stop());
    cameraStream = null;
  }
  showCameraModal.value = false;
}

function capturePhoto() {
  const video = cameraVideoRef.value;
  if (!video) return;

  const canvas = document.createElement('canvas');
  canvas.width = video.videoWidth;
  canvas.height = video.videoHeight;
  const ctx = canvas.getContext('2d');
  ctx.drawImage(video, 0, 0);
  
  canvas.toBlob((blob) => {
    const file = new File([blob], `capture_${Date.now()}.jpg`, { type: 'image/jpeg' });
    const fileObj = {
      file,
      name: file.name,
      isImage: true,
      isDoc: false,
      preview: URL.createObjectURL(blob)
    };
    attachedFiles.value.push(fileObj);
    closeCamera();
    nextTick(() => autoResize());
  }, 'image/jpeg', 0.8);
}

function handleFileSelect(event) {
  const files = event.target.files;
  if (!files.length) return;
  
  Array.from(files).forEach(file => {
    const isImage = file.type.startsWith('image/');
    const isDoc = !isImage;
    
    const fileObj = {
      file,
      name: file.name,
      isImage,
      isDoc,
      preview: isImage ? URL.createObjectURL(file) : null
    };
    
    attachedFiles.value.push(fileObj);
  });
  
  // Reset inputs
  if (fileInputRef.value) fileInputRef.value.value = '';
  if (cameraInputRef.value) cameraInputRef.value.value = '';
  
  nextTick(() => autoResize());
}

function handlePaste(event) {
  const items = (event.clipboardData || event.originalEvent.clipboardData).items;
  for (const item of items) {
    if (item.type.indexOf("image") !== -1) {
      const file = item.getAsFile();
      const fileObj = {
        file,
        name: `pasted_image_${Date.now()}.png`,
        isImage: true,
        isDoc: false,
        preview: URL.createObjectURL(file)
      };
      attachedFiles.value.push(fileObj);
      nextTick(() => autoResize());
    }
  }
}

function removeFile(index) {
  const file = attachedFiles.value[index];
  if (file.preview) {
    URL.revokeObjectURL(file.preview);
  }
  attachedFiles.value.splice(index, 1);
  nextTick(() => autoResize());
}

async function sendMessage() {
  const text = inputMessage.value.trim();
  const filesToSend = [...attachedFiles.value]; // Copy the files to send
  
  if (!text && filesToSend.length === 0) return;
  if (isTyping.value) return;

  const editingId = editingMessageId.value;
  
  // Clear edit state
  isEditing.value = false;
  editingMessageId.value = null;

  // Clear previews early
  attachedFiles.value = [];

  // Optimistic UI for User Message
  const tempId = Date.now();
  props.messages.push({
    id: tempId,
    role: 'user',
    content: text,
    attachments: filesToSend.map(f => ({
      url: f.preview, // Changed from path to url to match template
      name: f.name,
      isImage: f.isImage
    })),
    timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
  });

  inputMessage.value = '';

  nextTick(() => {
    if (inputRef.value) inputRef.value.style.height = 'auto';
  });

  scrollToBottom();
  isTyping.value = true;

  try {
    const formData = new FormData();
    formData.append('message', text);
    formData.append('model', selectedModel.value.id);
    if (props.activeConversationId) {
      formData.append('conversation_id', props.activeConversationId);
    }
    
    filesToSend.forEach((f, index) => {
      formData.append(`files[${index}]`, f.file);
    });

    const response = await axios.post('/chat', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });

    if (response.data.success) {
      // Find the optimistic message and update it or just refresh
      // For simplicity with Inertia, if it's a new conversation we redirect
      if (!props.activeConversationId) {
        // Revoke URLs before leaving
        filesToSend.forEach(f => { if (f.preview) URL.revokeObjectURL(f.preview); });
        router.visit(`/c/${response.data.conversation_id}`, { preserveScroll: true });
      } else {
        // Replace temp message or just append response
        // In local state, push assistant response
        props.messages.push(response.data.message);
        
        // Revoke blobs after successful upload
        filesToSend.forEach(f => { if (f.preview) URL.revokeObjectURL(f.preview); });
      }
    } else {
      showToast(response.data.error || 'Something went wrong', 'error');
    }
  } catch (error) {
    showToast(error.response?.data?.error || 'Failed to send message', 'error');
  } finally {
    isTyping.value = false;
    scrollToBottom();
    nextTick(() => inputRef.value?.focus());
  }
}

function toggleVoiceInput() {
  if (!window.isSecureContext && window.location.hostname !== 'localhost') {
    showToast('Voice input requires a secure connection (HTTPS) or localhost. Please check your URL.', 'error');
    return;
  }

  if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
    showToast('Speech recognition not supported in this browser', 'error');
    return;
  }

  if (isListening.value) {
    recognition.stop();
    return;
  }

  recognition = new webkitSpeechRecognition();
  recognition.continuous = false;
  recognition.interimResults = false;
  recognition.lang = 'en-US';

  recognition.onstart = () => {
    isListening.value = true;
  };

  recognition.onresult = (event) => {
    const transcript = event.results[0][0].transcript;
    inputMessage.value += transcript;
    autoResize();
  };

  recognition.onerror = (event) => {
    console.error('Speech recognition error:', event.error);
    if (event.error === 'not-allowed') {
      showToast('Microphone access denied. Please allow microphone permissions in your browser settings.', 'error');
    } else {
      showToast('Voice input failed: ' + event.error, 'error');
    }
    isListening.value = false;
  };

  recognition.onend = () => {
    isListening.value = false;
  };

  recognition.start();
}

// --- Voice Mode Logic ---
let voiceRecognition = null;

function openVoiceMode() {
  if (!window.isSecureContext && window.location.hostname !== 'localhost') {
    showToast('Voice mode requires a secure connection (HTTPS) or localhost.', 'error');
    return;
  }
  if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
    showToast('Speech recognition not supported in this browser', 'error');
    return;
  }
  if (!window.speechSynthesis) {
    showToast('Speech synthesis not supported in this browser', 'error');
    return;
  }

  showVoiceMode.value = true;
  voiceModeMuted.value = false;
  startVoiceListening();
}

function closeVoiceMode() {
  showVoiceMode.value = false;
  voiceModeState.value = 'idle';
  if (voiceRecognition) {
    voiceRecognition.stop();
  }
  window.speechSynthesis.cancel();
}

function toggleVoiceModePause() {
  voiceModeMuted.value = !voiceModeMuted.value;
  if (voiceModeMuted.value) {
    voiceModeState.value = 'idle';
    if (voiceRecognition) voiceRecognition.stop();
    window.speechSynthesis.cancel();
  } else {
    startVoiceListening();
  }
}

function startVoiceListening() {
  if (voiceModeMuted.value || !showVoiceMode.value) return;

  window.speechSynthesis.cancel(); // Stop talking if we are about to listen
  voiceModeState.value = 'listening';

  voiceRecognition = new (window.SpeechRecognition || window.webkitSpeechRecognition)();
  voiceRecognition.continuous = false;
  voiceRecognition.interimResults = false;
  
  // Use selected spoken language if possible, else auto
  voiceRecognition.lang = personalizationSettings.value.spokenLanguage === 'English' ? 'en-US' : 'en-US'; 

  voiceRecognition.onresult = async (event) => {
    const transcript = event.results[0][0].transcript;
    if (transcript.trim()) {
      await processVoiceInput(transcript);
    } else {
      startVoiceListening(); // Nothing said, start again
    }
  };

  voiceRecognition.onerror = (event) => {
    if (event.error !== 'aborted') {
      console.error('Voice Mode recognition error:', event.error);
    }
    // Only restart listening if we aren't intentionally paused or closed
    if (showVoiceMode.value && !voiceModeMuted.value && voiceModeState.value !== 'speaking' && voiceModeState.value !== 'thinking') {
        setTimeout(startVoiceListening, 500);
    }
  };

  voiceRecognition.onend = () => {
    // If it ends naturally and we aren't processing anything, listen again
    if (showVoiceMode.value && !voiceModeMuted.value && voiceModeState.value === 'listening') {
       startVoiceListening();
    }
  };

  try {
    voiceRecognition.start();
  } catch (e) {
    console.error("Could not start recognition", e);
  }
}

async function processVoiceInput(text) {
  voiceModeState.value = 'thinking';
  
  // Send as a normal message
  inputMessage.value = text;
  
  // Wait to get response
  try {
    await sendMessage();
    
    // Once sendMessage completes, the latest bot message is the response
    const lastMessage = props.messages[props.messages.length - 1];
    if (lastMessage && lastMessage.role === 'assistant') {
       speakAIResponse(lastMessage.content);
    } else {
       // Failed, start listening again
       startVoiceListening();
    }
  } catch (error) {
    console.error("Voice mode error:", error);
    startVoiceListening();
  }
}

function stripMarkdown(text) {
  // Simple regex to remove bold, italic, code blocks, etc for cleaner reading
  return text
    .replace(/\*\*(.*?)\*\*/g, '$1') // bold
    .replace(/\*(.*?)\*/g, '$1') // italic
    .replace(/`(.*?)`/g, '$1') // code
    .replace(/```[\s\S]*?```/g, 'Code block removed.') // multiline code
    .replace(/\[([^\]]+)\]\([^\)]+\)/g, '$1') // links
    .replace(/<[^>]*>?/gm, ''); // html tags
}

function speakAIResponse(text) {
  if (!showVoiceMode.value || voiceModeMuted.value) return;

  voiceModeState.value = 'speaking';
  const cleanText = stripMarkdown(text);
  const utterance = new SpeechSynthesisUtterance(cleanText);

  // Apply voice settings
  const availableVoices = window.speechSynthesis.getVoices();
  const selectedVoiceName = personalizationSettings.value.voice;
  const voiceDef = voices.find(v => v.name === selectedVoiceName);
  
  let activeVoice = null;
  if (availableVoices.length > 0) {
    const voiceIndex = voices.findIndex(v => v.name === selectedVoiceName);
    activeVoice = availableVoices[voiceIndex % availableVoices.length] || availableVoices[0];
  }

  if (activeVoice) {
    utterance.voice = activeVoice;
  }
  
  utterance.rate = voiceDef ? voiceDef.rate : 1.0;
  utterance.pitch = voiceDef ? voiceDef.pitch : 1.0;

  utterance.onend = () => {
    // Start listening again after speaking
    if (showVoiceMode.value && !voiceModeMuted.value) {
      startVoiceListening();
    }
  };

  utterance.onerror = (e) => {
    console.error("Speech Synthesis Error:", e);
    if (showVoiceMode.value && !voiceModeMuted.value) {
      startVoiceListening();
    }
  };

  window.speechSynthesis.speak(utterance);
}

// Speak Message Function for Normal Chat TTS
function speakMessage(msg) {
  const cleanText = stripMarkdown(msg.content);
  const utterance = new SpeechSynthesisUtterance(cleanText);

  // Apply voice settings
  const availableVoices = window.speechSynthesis.getVoices();
  const selectedVoiceName = personalizationSettings.value.voice;
  const voiceDef = voices.find(v => v.name === selectedVoiceName);
  
  let activeVoice = null;
  if (availableVoices.length > 0) {
    const voiceIndex = voices.findIndex(v => v.name === selectedVoiceName);
    activeVoice = availableVoices[voiceIndex % availableVoices.length] || availableVoices[0];
  }

  if (activeVoice) {
    utterance.voice = activeVoice;
  }
  
  utterance.rate = voiceDef ? voiceDef.rate : 1.0;
  utterance.pitch = voiceDef ? voiceDef.pitch : 1.0;

  window.speechSynthesis.speak(utterance);
}

// Ensure voices are loaded (Chrome sometimes needs a trigger)
if (window.speechSynthesis) {
  window.speechSynthesis.onvoiceschanged = () => {
    window.speechSynthesis.getVoices();
  };
}

// Test Voice Function
function playTestVoice() {
  const selected = personalizationSettings.value.voice;
  const voiceDef = voices.find(v => v.name === selected);
  if (!voiceDef) {
    showToast('Voice not found', 'error');
    return;
  }

  const utterance = new SpeechSynthesisUtterance("Hello, this is a test of the voice. How does it sound?");
  const availableVoices = window.speechSynthesis.getVoices();
  let activeVoice = null;
  if (availableVoices.length > 0) {
    const voiceIndex = voices.findIndex(v => v.name === selected);
    activeVoice = availableVoices[voiceIndex % availableVoices.length] || availableVoices[0];
  }
  if (activeVoice) {
    utterance.voice = activeVoice;
  }
  utterance.rate = voiceDef.rate;
  utterance.pitch = voiceDef.pitch;
  window.speechSynthesis.speak(utterance);
}

// ---------------------------------------------
function editMessage(msg) {
  inputMessage.value = msg.content;
  isEditing.value = true;
  editingMessageId.value = msg.id;
  nextTick(() => {
    inputRef.value?.focus();
    autoResize();
  });
}

function copyToClipboard(text) {
  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(text).then(() => {
      showToast('Copied to clipboard!');
    }).catch(() => {
      showToast('Failed to copy', 'error');
    });
  } else {
    // Fallback for non-secure contexts or older browsers
    try {
      const textArea = document.createElement("textarea");
      textArea.value = text;
      textArea.style.position = "fixed";
      textArea.style.left = "-9999px";
      textArea.style.top = "0";
      document.body.appendChild(textArea);
      textArea.focus();
      textArea.select();
      const successful = document.execCommand('copy');
      document.body.removeChild(textArea);
      if (successful) {
        showToast('Copied to clipboard!');
      } else {
        showToast('Failed to copy', 'error');
      }
    } catch (err) {
      showToast('Failed to copy', 'error');
    }
  }
}

function sendPlaceholder(text) {
  inputMessage.value = text;
  sendMessage();
}

function startNewChat() {
  router.visit('/', {
    onSuccess: () => {
      sidebarOpen.value = false;
      inputRef.value?.focus();
    }
  });
}

function switchConversation(id) {
  router.visit(`/c/${id}`, {
    preserveScroll: true,
    onSuccess: () => {
      sidebarOpen.value = false;
      scrollToBottom();
    }
  });
}

function deleteConversation(id) {
  confirmModal.value = { show: true, id };
}

// Code block copy handler
const copyIconHtml = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-80"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>';
const checkIconHtml = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="text-green-500"><polyline points="20 6 9 17 4 12"></polyline></svg>';

onMounted(() => {
  window.addEventListener('click', (e) => {
    const copyBtn = e.target.closest('.copy-code-btn');
    if (copyBtn) {
      const code = copyBtn.getAttribute('data-code');
      copyToClipboard(decodeURIComponent(code));
      copyBtn.innerHTML = checkIconHtml;
      setTimeout(() => copyBtn.innerHTML = copyIconHtml, 2000);
    }
  });
});

async function confirmDelete() {
  const id = confirmModal.value.id;
  confirmModal.value.show = false;
  
  try {
    await axios.delete(`/conversations/${id}`);
    if (props.activeConversationId === id) {
      startNewChat();
    } else {
      router.reload({ preserveScroll: true });
    }
    showToast('Conversation deleted');
  } catch {
    showToast('Failed to delete', 'error');
  }
}

async function deleteAllChats() {
  try {
    await axios.delete('/conversations');
    showSettingsModal.value = false;
    startNewChat(); // Will also clear the currently active chat
    showToast('All conversations deleted');
  } catch {
    showToast('Failed to delete conversations', 'error');
  }
}

function scrollToBottom() {
  nextTick(() => {
    const el = messagesContainer.value;
    if (el) el.scrollTo({ top: el.scrollHeight, behavior: 'smooth' });
  });
}

function renderMarkdown(text) {
  if (!text) return '';

  const codeBlocks = [];
  
  // 1. Extract and process Code Blocks first (to avoid double escaping)
  let processedText = text.replace(/```(\w*)\n([\s\S]*?)```/g, (match, lang, code) => {
    const trimmedCode = code.trim();
    const escapedCodeForButton = encodeURIComponent(trimmedCode);
    const placeholder = `__CODE_BLOCK_${codeBlocks.length}__`;
    
    // Highlight logic
    let highlightedCode = trimmedCode;
    let detectedLang = lang || 'code';
    if (window.hljs) {
      try {
        if (lang && hljs.getLanguage(lang)) {
          highlightedCode = hljs.highlight(trimmedCode, { language: lang }).value;
        } else {
          const res = hljs.highlightAuto(trimmedCode);
          highlightedCode = res.value;
          detectedLang = res.language || 'code';
        }
      } catch (e) { highlightedCode = trimmedCode; }
    }

    // 'Ultra-Minimalist' Design (No Background)
    const blockHtml = `<div class="code-block-wrapper my-8 rounded-2xl overflow-hidden bg-[#0d0d0d] border border-white/5 group/block">
        <div class="flex items-center justify-between px-5 py-3 bg-[#2a2a2a] border-b border-white/5">
          <div class="flex items-center gap-2.5">
             <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-white/70">
                <path d="M16 18l6-6-6-6M8 6l-6 6 6 6"/>
             </svg>
             <span class="text-[11px] font-sans font-bold text-white/90 uppercase tracking-widest">${detectedLang.toUpperCase()}</span>
          </div>
          <button data-code="${escapedCodeForButton}" class="copy-code-btn p-1 flex items-center gap-1.5 rounded-md text-white/50 hover:text-white transition-all active:scale-95 group/copy" title="Copy code">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-80">
              <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
              <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
            </svg>
          </button>
        </div>
        <div class="p-6 overflow-x-hidden bg-transparent">
          <pre class="text-[14px] leading-relaxed font-mono hljs ${detectedLang} bg-transparent" style="white-space: pre-wrap; word-break: break-all; border:none; padding:0; margin:0;"><code>${highlightedCode}</code></pre>
        </div>
      </div>`;
    
    codeBlocks.push(blockHtml);
    return placeholder;
  });

  // 2. Escape HTML for the rest of the text
  processedText = processedText
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');

  // 3. Handle Inline Code, Bold, Lists in text
  processedText = processedText
    .replace(/`([^`]+)`/g, '<code class="px-1.5 py-0.5 rounded bg-indigo-500/10 text-indigo-300 font-mono text-[12px]">$1</code>')
    .replace(/\*\*(.+?)\*\*/g, '<strong class="font-bold text-white">$1</strong>')
    .replace(/^\s*[-*]\s+(.+)$/gm, '<li class="ml-4 list-disc text-gray-300">$1</li>')
    .replace(/(<li>.+<\/li>)/s, '<ul class="my-4 space-y-2">$1</ul>');

  // 4. Cleanup Newlines FIRST
  processedText = processedText
    .replace(/\n\n/g, '<p class="mt-4"></p>') // Paragraph spacing
    .replace(/\n/g, '<br>');

  // 5. Restore Code Blocks LAST (to prevent <br> injection inside our design)
  codeBlocks.forEach((html, i) => {
    processedText = processedText.replace(`__CODE_BLOCK_${i}__`, html);
  });

  return `<div class="prose-container">${processedText}</div>`;
}

watch(() => props.messages, () => scrollToBottom(), { deep: true });
</script>

<style>
/* ══ Custom Scrollbar ══ */
.custom-scrollbar::-webkit-scrollbar { width: 8px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { border-radius: 10px; }

.scrollbar-dark::-webkit-scrollbar-thumb { background: #2f2f2f; }
.scrollbar-light::-webkit-scrollbar-thumb { background: #e5e7eb; }

/* ══ Animations ══ */
@keyframes fade-in {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in { animation: fade-in 0.4s ease-out; }

.menu-fade-enter-active,
.menu-fade-leave-active {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.menu-fade-enter-from,
.menu-fade-leave-to {
  opacity: 0;
  transform: translateY(10px) scale(0.95);
}

@keyframes voice-bar {
  0%, 100% { height: 4px; }
  50% { height: 18px; }
}
.animate-voice-bar { animation: voice-bar 0.6s ease-in-out infinite; }

/* ══ Prose Specifics ══ */
.prose-chat strong { font-weight: 700; color: inherit; }
.prose-chat pre { margin: 0; }
.prose-chat p { margin: 0; }

/* ══ HLJS Overrides to match our custom card ══ */
.hljs {
  background: transparent !important;
  padding: 0 !important;
}
</style>
