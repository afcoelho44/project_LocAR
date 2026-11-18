<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $participation->activity->name }}</h2>
            @if ($question)
                <span class="text-sm text-gray-600">Etapa {{ $number }} de {{ $total }}</span>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            @if ($clue)
                {{-- acertou: exibe a pista --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-center">
                    <p class="text-2xl font-semibold text-green-600">Resposta correta!</p>

                    @if ($clue['text'])
                        <div class="mt-6 p-4 rounded-lg bg-amber-50 border border-amber-200 text-left">
                            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Pista</p>
                            <p class="mt-1 text-lg text-gray-800">{{ $clue['text'] }}</p>
                        </div>
                    @endif

                    @if ($clue['finished'])
                        <a href="{{ route('play.show', $participation) }}"
                           class="mt-6 inline-block px-6 py-3 bg-green-600 text-white text-sm uppercase rounded-md">
                            Concluir percurso
                        </a>
                    @else
                        <a href="{{ route('play.ar', $participation) }}"
                           class="mt-6 inline-block px-6 py-3 bg-gray-800 text-white text-sm uppercase rounded-md">
                            Ir para o próximo ponto
                        </a>
                    @endif
                </div>
            @else
                {{-- pergunta --}}
                <form method="POST" action="{{ route('play.answer', $participation) }}"
                      class="bg-white shadow-sm sm:rounded-lg p-6">
                    @csrf
                    <h3 class="text-lg font-semibold text-gray-800">{{ $question->description }}</h3>

                    <div class="mt-4 space-y-2">
                        @foreach ($question->answers as $a)
                            <label class="flex items-start gap-3 p-3 border border-gray-300 rounded-lg cursor-pointer has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50">
                                <input type="radio" name="answer_id" value="{{ $a->id }}" class="mt-1"
                                       @checked(old('answer_id') == $a->id)>
                                <span><strong>{{ $a->alternative }})</strong> {{ $a->description }}</span>
                            </label>
                        @endforeach
                    </div>

                    <x-input-error :messages="$errors->get('answer_id')" class="mt-3" />

                    <button type="submit"
                            class="mt-6 w-full px-6 py-3 bg-blue-600 text-white text-sm uppercase rounded-md">
                        Responder
                    </button>
                </form>

                <a href="{{ route('play.ar', $participation) }}" class="text-sm text-gray-600 underline">Voltar para a RA</a>
            @endif
        </div>
    </div>
</x-app-layout>
